const fs = require('fs');
const file = 'app/Http/Controllers/App/ScheduledTasksController.php';
let content = fs.readFileSync(file, 'utf8');

const additionalMethods = `
    /**
     * Display the specified scheduled task (Vue).
     */
    public function show(ScheduledTask $scheduledTask)
    {
        abort_if(Gate::denies('scheduled_task_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $scheduledTask->load('driver', 'client', 'from_location', 'to_location', 'parent', 'parent.children');

        // Fetch children instances if this is a parent, else fetch parent and its other children
        $relatedTasks = $scheduledTask->parent_id
            ? ScheduledTask::where('parent_id', $scheduledTask->parent_id)
                ->orWhere('id', $scheduledTask->parent_id)
                ->with(['driver', 'client', 'from_location', 'to_location'])
                ->get()
            : ScheduledTask::where('parent_id', $scheduledTask->id)
                ->orWhere('id', $scheduledTask->id)
                ->with(['driver', 'client', 'from_location', 'to_location'])
                ->get();

        return Inertia::render('Tasks/ScheduledTaskShow', [
            'task' => $scheduledTask,
            'relatedTasks' => $relatedTasks
        ]);
    }

    /**
     * Update scheduled task(s).
     */
    public function update(Request $request, ScheduledTask $scheduledTask)
    {
        abort_if(Gate::denies('scheduled_task_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'status' => 'required',
            'task_type' => 'required',
        ]);

        $scheduledTask->update($request->all());

        if ($request->update_related) {
            // Update all children with same details except for day and selected hour
            $dataToUpdate = $request->except(['day', 'selected_hour', 'update_related']);
            
            if ($scheduledTask->parent_id) {
                // Update parent and siblings
                ScheduledTask::where('parent_id', $scheduledTask->parent_id)
                    ->orWhere('id', $scheduledTask->parent_id)
                    ->update($dataToUpdate);
            } else {
                // Update children
                ScheduledTask::where('parent_id', $scheduledTask->id)
                    ->update($dataToUpdate);
            }
        }

        return response()->json(['message' => 'Scheduled task(s) updated successfully.']);
    }

    /**
     * Delete a scheduled task.
     */
    public function destroy(ScheduledTask $scheduledTask)
    {
        abort_if(Gate::denies('scheduled_task_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $scheduledTask->delete();

        return response()->json(['message' => 'Scheduled task deleted successfully.']);
    }
`;

content = content.replace(/}\s*$/g, additionalMethods + "\n}");

fs.writeFileSync(file, content);
console.log("Patched ScheduledTasksController.php");
