<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftTemplatesController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // SPA list + its JSON reloads. The classic Blade page is officially removed.
        $query = ShiftTemplate::query();

        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $query->where(function ($q) use ($kw) {
                $q->where('id', 'LIKE', "%{$kw}%")->orWhere('name', 'LIKE', "%{$kw}%");
            });
        }

        $sortBy = in_array($request->input('sort_by'), ['id', 'name', 'start_time', 'end_time']) ? $request->sort_by : 'id';
        $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->count();
        $offset = ($page - 1) * $pageSize;

        $seq = $offset;
        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($t) use (&$seq) {
            $seq++;
            $hm = function ($v) {
                try { return $v ? \Carbon\Carbon::parse($v)->format('H:i') : null; } catch (\Throwable $e) { return $v; }
            };
            return [
                'sequence'   => $seq,
                'id'         => $t->id,
                'name'       => $t->name,
                'start_time' => $hm($t->start_time),
                'end_time'   => $hm($t->end_time),
                'created_at' => $t->created_at ? $t->created_at->format('Y-m-d H:i') : null,
            ];
        });

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['rows' => $rows, 'total' => $total]);
        }

        return \Inertia\Inertia::render('ShiftTemplates/ShiftTemplatesList', [
            'initialRows'  => $rows,
            'initialTotal' => $total,
        ]);
    }

    /** Create a shift template from the SPA modal — same rules as the classic store(). */
    public function storePopup(Request $request)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'name'       => 'required',
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        ShiftTemplate::create($request->only(['name', 'start_time', 'end_time']));

        return redirect()->back()->with('success', 'Shift template created successfully');
    }

    /** Update a shift template from the SPA modal — same rules as the classic update(). */
    public function updatePopup(Request $request, ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'name'       => 'required',
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        $shiftTemplate->update($request->only(['name', 'start_time', 'end_time']));

        return redirect()->back()->with('success', 'Shift template updated successfully');
    }

    public function create()
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.shiftTemplates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        ShiftTemplate::create($request->all());

        return redirect()->route('admin.shift-templates.index');
    }

    public function edit(ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.shiftTemplates.edit', compact('shiftTemplate'));
    }

    public function update(Request $request, ShiftTemplate $shiftTemplate)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $shiftTemplate->update($request->all());

        return redirect()->route('admin.shift-templates.index');
    }

    public function show(ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.shiftTemplates.show', compact('shiftTemplate'));
    }

    public function destroy(ShiftTemplate $shiftTemplate)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $shiftTemplate->delete();

        return back();
    }

    public function massDestroy(Request $request)
    {
        ShiftTemplate::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
