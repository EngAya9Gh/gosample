const fs = require('fs');

// 1. Add route to routes/web.php
let webContent = fs.readFileSync('routes/web.php', 'utf8');
webContent = webContent.replace(
  "Route::resource('swaprequests', 'SwaprequestController');",
  "Route::post('swaprequests/tasks/list', 'SwaprequestController@getTasksForDriver')->name('swaprequests.tasks.list');\n    Route::resource('swaprequests', 'SwaprequestController');"
);
fs.writeFileSync('routes/web.php', webContent);

// 2. Add method to SwaprequestController
let ctrlContent = fs.readFileSync('app/Http/Controllers/Admin/SwaprequestController.php', 'utf8');
const methodCode = `
    public function getTasksForDriver(Request $request)
    {
        $tasks = \\App\\Models\\Task::with('from')->where('status', '<>', 'NO_SAMPLES')
            ->leftJoin('shipment', 'shipment.task_id', '=', 'tasks.id')
            ->where('tasks.driver_id', $request->driver_id)
            ->where('tasks.status', '<>', 'CLOSED')
            ->where('tasks.status', '<>', 'NEW')
            ->select('tasks.*', 'shipment.dropoff_otp')->get();

        return response()->json([
            'status' => true,
            'data' => $tasks
        ]);
    }
`;
ctrlContent = ctrlContent.replace(
    /public function store\(/,
    methodCode + '\n    public function store('
);
fs.writeFileSync('app/Http/Controllers/Admin/SwaprequestController.php', ctrlContent);

// 3. Update Vue file
let vueContent = fs.readFileSync('resources/js/vue/views/SwapRequests/SwapRequestForm.vue', 'utf8');
vueContent = vueContent.replace(
    "/api/swap/tasks/list",
    "/admin/swaprequests/tasks/list"
);
fs.writeFileSync('resources/js/vue/views/SwapRequests/SwapRequestForm.vue', vueContent);

console.log("Patched admin swap logic!");
