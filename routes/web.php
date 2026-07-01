<?php

// Route::redirect('/welcome', '/welcome');

use App\Http\Controllers\DriverController;

use App\Http\Controllers\EmergencyController;

// New Vue 3 + Tailwind front-end (Inertia.js). Served under /app, alongside the
// classic Velzon panel. Each screen is a Laravel route returning an Inertia page;
// data comes through Inertia props (no separate JSON API). Migrated screens are
// added here one module at a time; everything else falls back to a ComingSoon page.
Route::middleware(['auth'])->prefix('app')->group(function () {
    Route::get('/', fn () => redirect('/app/dashboard'));
    Route::get('dashboard', [\App\Http\Controllers\App\DashboardController::class, 'index'])->name('app.dashboard');
    Route::get('delayeddashboard', [\App\Http\Controllers\App\DelayedDashboardController::class, 'index'])->name('app.delayeddashboard');
    Route::get('car-dashboard', [\App\Http\Controllers\App\CarDashboardController::class, 'index'])->name('app.car-dashboard');

    // Tasks (plan 08-tasks.md)
    Route::get('admin/tasks', [\App\Http\Controllers\App\TasksController::class, 'index'])->name('app.admin.tasks');
    Route::get('admin/tasks/{task}', [\App\Http\Controllers\App\TasksController::class, 'show'])->name('app.admin.tasks.show');
    Route::put('admin/tasks/{task}/update-times', [\App\Http\Controllers\App\TasksController::class, 'updateTimes'])->name('app.admin.tasks.updateTimes');

    // Drivers
    Route::get('admin/drivers', [\App\Http\Controllers\App\DriversController::class, 'index'])->name('app.admin.drivers.index');
    Route::get('admin/drivers/create', [\App\Http\Controllers\App\DriversController::class, 'create'])->name('app.admin.drivers.create');
    Route::post('admin/drivers', [\App\Http\Controllers\App\DriversController::class, 'store'])->name('app.admin.drivers.store');
    Route::get('admin/drivers/{driver}', [\App\Http\Controllers\App\DriversController::class, 'show'])->name('app.admin.drivers.show');
    Route::get('admin/drivers/{driver}/edit', [\App\Http\Controllers\App\DriversController::class, 'edit'])->name('app.admin.drivers.edit');
    Route::put('admin/drivers/{driver}', [\App\Http\Controllers\App\DriversController::class, 'update'])->name('app.admin.drivers.update');
    Route::delete('admin/drivers/massDestroy', [\App\Http\Controllers\App\DriversController::class, 'massDestroy'])->name('app.admin.drivers.massDestroy');
    Route::delete('admin/drivers/{driver}', [\App\Http\Controllers\App\DriversController::class, 'destroy'])->name('app.admin.drivers.destroy');
    Route::get('admin/drivers/{driver}/tasks', [\App\Http\Controllers\App\DriversController::class, 'showTasks'])->name('app.admin.drivers.tasks');
    Route::post('admin/drivers/{driver}/tasks/reorder', [\App\Http\Controllers\App\DriversController::class, 'reorderTasks'])->name('app.admin.drivers.tasks.reorder');
    Route::post('admin/drivers/{driver}/tasks/smartSort', [\App\Http\Controllers\App\DriversController::class, 'smartSortTasks'])->name('app.admin.drivers.tasks.smartSort');

    // Catch-all: not-yet-migrated screens show a "being migrated" page so the shell
    // stays fully navigable. Specific routes above take precedence.
    Route::get('{any}', fn () => \Inertia\Inertia::render('system/ComingSoon'))
        ->where('any', '.*')->name('app.fallback');
});

Route::redirect('/', '/login');
// Route::redirect('/login', '/login');
Route::get('/home', function () {
    if (session('status')) {
        return redirect()->route('admin.home')->with('status', session('status'));
    }

    return redirect()->route('admin.home');
});

Route::middleware(['auth'])->group(function () {
//    Route::get('/update-cars',[App\Http\Controllers\HomeController::class, 'updateCar']);
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/daily-operation', [App\Http\Controllers\DailyOperationController::class, 'index'])->name('operation');
    Route::get('/car-dashboard', [App\Http\Controllers\CarDashboardController::class, 'index'])->name('cardashboard');
    Route::post('/daily-operation', [App\Http\Controllers\DailyOperationController::class, 'index'])->name('operation.search');
    Route::get('/tasks-dashboard', [App\Http\Controllers\HomeController::class, 'tasksdashboard'])->name('tasksdashboard');
    Route::post('/tasks-dashboard', [App\Http\Controllers\HomeController::class, 'tasksdashboard'])->name('tasksdashboard.search');
    Route::get('/map', [App\Http\Controllers\HomeController::class, 'map'])->name('map');
    Route::post('/map', [App\Http\Controllers\HomeController::class, 'map'])->name('map.search');
    Route::post('/map/filter', [App\Http\Controllers\HomeController::class, 'filterMap'])->name('map.filter');
    Route::get('/delayeddashboard', [App\Http\Controllers\DelayedDashboardController::class, 'index'])->name('delayeddashboard');
    Route::get('/welcome', [App\Http\Controllers\HomeController::class, 'welcome'])->name('welcome');
    Route::get('/driver-locations', [App\Http\Controllers\HomeController::class, 'getDriverLocations']);
    Route::post('/samples/types/report','App\Http\Controllers\SampleController@report');
});


Auth::routes(['register' => false]);

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'App\Http\Controllers\Admin', 'middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    // Permissions
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::resource('permissions', 'PermissionsController');

    // Roles
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::resource('roles', 'RolesController');

    // Users
    Route::delete('users/destroy', 'UsersController@massDestroy')->name('users.massDestroy');
    Route::resource('users', 'UsersController');

    // Audit Logs
    Route::resource('audit-logs', 'AuditLogsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

    // Drivers
    Route::delete('drivers/destroy', 'DriversController@massDestroy')->name('drivers.massDestroy');
    Route::get('drivers/{id}/get-shifts', 'DriversController@getShifts')->name('drivers.getShifts');
    Route::post('drivers/{driver}/tasks/reorder', '\App\Http\Controllers\DriverController@reorderTasks')->name('drivers.tasks.reorder');
    Route::post('drivers/{driver}/tasks/smart-sort', '\App\Http\Controllers\DriverController@smartSortTasks')->name('drivers.tasks.smartSort');
    Route::resource('drivers', 'DriversController');

    // Cars
    Route::delete('cars/destroy', 'CarsController@massDestroy')->name('cars.massDestroy');
    Route::resource('cars', 'CarsController');

    // Attendances
    Route::delete('attendances/destroy', 'AttendancesController@massDestroy')->name('attendances.massDestroy');
    Route::resource('attendances', 'AttendancesController');

    // Shift Templates
    Route::delete('shift-templates/destroy', 'ShiftTemplatesController@massDestroy')->name('shift-templates.massDestroy');
    Route::resource('shift-templates', 'ShiftTemplatesController');

    // Barcodes
    Route::get('barcodes/generate', 'BarcodesController@generate')->name('barcodes.generate');
    Route::post('barcodes/generate', 'BarcodesController@generateBarcodes')->name('barcodes.generateBarcodes');
    Route::delete('barcodes/destroy', 'BarcodesController@massDestroy')->name('barcodes.massDestroy');
    Route::resource('barcodes', 'BarcodesController');

    // Car Driver
    Route::delete('car-drivers/destroy', 'CarDriverController@massDestroy')->name('car-drivers.massDestroy');
    Route::resource('car-drivers', 'CarDriverController');

    // Car Link History
    Route::delete('car-link-histories/destroy', 'CarLinkHistoryController@massDestroy')->name('car-link-histories.massDestroy');
    Route::resource('car-link-histories', 'CarLinkHistoryController');

    // Clients
    Route::delete('clients/destroy', 'ClientsController@massDestroy')->name('clients.massDestroy');
    Route::post('clients/media', 'ClientsController@storeMedia')->name('clients.storeMedia');
    Route::post('clients/ckmedia', 'ClientsController@storeCKEditorImages')->name('clients.storeCKEditorImages');
    Route::resource('clients', 'ClientsController');

    // Locations
    Route::delete('locations/destroy', 'LocationsController@massDestroy')->name('locations.massDestroy');
    Route::resource('locations', 'LocationsController');

    // Containers
    Route::delete('containers/destroy', 'ContainersController@massDestroy')->name('containers.massDestroy');
    Route::resource('containers', 'ContainersController');

    // Client Location
    Route::delete('client-locations/destroy', 'ClientLocationController@massDestroy')->name('client-locations.massDestroy');
    Route::resource('client-locations', 'ClientLocationController');

    // Client Accounts
    Route::delete('client-accounts/destroy', 'ClientAccountsController@massDestroy')->name('client-accounts.massDestroy');
    Route::resource('client-accounts', 'ClientAccountsController');

    // Contacts
    Route::delete('contacts/destroy', 'ContactsController@massDestroy')->name('contacts.massDestroy');
    Route::resource('contacts', 'ContactsController');

//    Route::get('tasks/unused', 'TasksController@unUsedTasks')->name('tasks.unuseds');

    // Tasks
    Route::post('tasks', 'TasksController@export')->name('tasks.export');

    Route::get('tasks/unused', 'TasksController@unUsedTasks')->name('tasks.unused');
    Route::get('tasks/scan', 'TasksController@scan')->name('tasks.scan');
    Route::get('tasks/missing', 'TasksController@missing')->name('tasks.missing');
    Route::delete('tasks/destroy', 'TasksController@massDestroy')->name('tasks.massDestroy');
    Route::get('tasks/newshow/{id}', 'TasksController@newShow');
    Route::put('tasks/{task}/update-times', 'TasksController@updateTimes')->name('tasks.updateTimes');
    Route::resource('tasks', 'TasksController');
    Route::get('swap-tasks', 'TaskSwapController@index')->name('swapTask.index');
    Route::get('swap-tasks/{taks}', 'TaskSwapController@index')->name('swapTask.show');
    Route::get('/swap-export-excel', 'TaskSwapController@exportExcelDetails')->name('swapTask.export-excel');
    Route::post('swap-task-report', 'TaskSwapController@export')->name('swapReportExport');

    Route::get('scheduled-driver', 'ScheduledTaskController@indexSchedule')->name('tasks.indexSchedule');
    
    // Client Driver Tracking
    Route::get('driver-tracking', 'DriverTrackingController@clientDashboard')->name('driver-tracking');

    Route::get('/export-excel', 'TasksController@exportExcelDetails')->name('tasks.export-excel');
    Route::get('/tasks/export-status/{token}', 'TasksController@exportStatus')->name('tasks.export.status');


    // Samples
    Route::delete('samples/destroy', 'SamplesController@massDestroy')->name('samples.massDestroy');
    Route::resource('samples', 'SamplesController');


    Route::get('lost', 'SamplesController@lost')->name('samples.lost');
    Route::get('pickupdelayed', 'TasksController@pickupdelayed')->name('tasks.pickupdelayed');
    Route::get('dropdelayed', 'TasksController@dropdelayed')->name('tasks.dropdelayed');
    Route::get('collectedDelayed', 'TasksController@collectedDelayed')->name('tasks.collectedDelayed');
    Route::get('outfreezerdelayed', 'TasksController@outfreezerdelayed')->name('tasks.outfreezerdelayed');


    // Terms
    Route::delete('terms/destroy', 'TermsController@massDestroy')->name('terms.massDestroy');
    Route::resource('terms', 'TermsController');

    // Elm Notifications
    Route::resource('elm-notifications', 'ElmNotificationsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

    // Driver Schedule
    Route::delete('driver-schedules/destroy', 'DriverScheduleController@massDestroy')->name('driver-schedules.massDestroy');
    Route::resource('driver-schedules', 'DriverScheduleController');

    // Swaprequest
    Route::delete('swaprequests/destroy', 'SwaprequestController@massDestroy')->name('swaprequests.massDestroy');
    Route::resource('swaprequests', 'SwaprequestController');


     // Zones
     Route::delete('zones/destroy', 'ZonesController@massDestroy')->name('zones.massDestroy');
     Route::resource('zones', 'ZonesController');

     // Client Driver
     Route::delete('client-drivers/destroy', 'ClientDriverController@massDestroy')->name('client-drivers.massDestroy');
     Route::resource('client-drivers', 'ClientDriverController');

    // Audit Logs
    Route::resource('audit-logs', 'AuditLogsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

     // Notifications
     Route::resource('notifications', 'NotificationsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

     Route::resource('shipments', 'ShipmentsController', ['except' => ['edit', 'update', 'destroy']]);

     // Reports
     Route::get('reports', 'ReportsController@index')->name('reports.index');
     Route::get('reports/daily', 'ReportsController@daily')->name('reports.daily');
     Route::get('reports/weekly', 'ReportsController@weekly')->name('reports.weekly');
     Route::get('reports/monthly', 'ReportsController@monthly')->name('reports.monthly');
     Route::get('reports/monthly/export', 'ReportsController@exportMonthly')->name('reports.exportMonthly');
     Route::get('reports/performance', 'ReportsController@performance')->name('reports.performance');
     Route::get('header/notifications-data', 'ReportsController@getHeaderNotifications')->name('header.notifications');

     Route::get('system-calendar', 'SystemCalendarController@index')->name('systemCalendar');

     Route::post('task-report', 'TasksController@export')->name('reportExport');

      // Scheduled Task
    Route::delete('scheduled-tasks/destroy', 'ScheduledTaskController@massDestroy')->name('scheduled-tasks.massDestroy');
    Route::delete('scheduled-tasks/{scheduledTask}/children/destroy', 'ScheduledTaskController@massDestroyChildren')->name('scheduled-tasks.childrenMassDestroy');
    Route::resource('scheduled-tasks', 'ScheduledTaskController');

    Route::get('schedule/quick', 'ScheduledTaskController@quick')->name('scheduled-tasks.quick');
    Route::post('schedule/quick', 'ScheduledTaskController@quickAction')->name('scheduled-tasks.quickAction');
    Route::get('schedule/delete/{scheduledTask}/parents', 'ScheduledTaskController@deleteBasedOnParent')->name('scheduled-tasks.deleteAllParent');
    Route::get('scheduled-tasks/search/drivers', 'ScheduledTaskController@searchDrivers')->name('scheduled-tasks.searchDrivers');
    Route::get('scheduled-tasks/search/locations', 'ScheduledTaskController@searchLocations')->name('scheduled-tasks.searchLocations');
    // Money Transfer
    Route::delete('money-transfers/destroy', 'MoneyTransferController@massDestroy')->name('money-transfers.massDestroy');
    Route::resource('money-transfers', 'MoneyTransferController');

    Route::get('schedules/logs', 'ScheduleLogController@index')->name('schedules.logs');

    Route::post('shipments/{shipment}/update-notification', 'ShipmentsController@updateAyenatiNotification')->name('shipments.updateNotification');
    Route::post('shipments/{shipment}/assign-driver', 'ShipmentsController@assignDriver')->name('shipments.assignDriver');
    Route::post('shipments/{shipment}/deliver', 'ShipmentsController@deliver')->name('shipments.deliver');

    // Api Ayenati
    Route::resource('api-ayenatis', 'ApiAyenatiController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

    // Delete Permissions (editable by user_id = 1 only)
    Route::get('delete-permissions', 'DeletePermissionsController@index')->name('delete-permissions.index');
    Route::post('delete-permissions', 'DeletePermissionsController@store')->name('delete-permissions.store');
    Route::delete('delete-permissions/{userId}', 'DeletePermissionsController@destroy')->name('delete-permissions.destroy');

});
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'App\Http\Controllers\Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', 'ChangePasswordController@edit')->name('password.edit');
        Route::post('password', 'ChangePasswordController@update')->name('password.update');
        Route::post('profile', 'ChangePasswordController@updateProfile')->name('password.updateProfile');
        Route::post('profile/destroy', 'ChangePasswordController@destroy')->name('password.destroyProfile');
    }
});
// Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// Route::get('admin/drivers/{driver}/tasks', [DriverController::class, 'tasksOfDriver']);
// Route::post('admin/drivers/{driver}/tasks/reorder', [DriverController::class, 'reorderTasks']);

Route::get('admin/drivers/{driver}/tasks', [DriverController::class, 'showTasks'])
    ->name('admin.drivers.tasks');

Route::post('admin/drivers/{driver}/tasks/reorder', [DriverController::class, 'reorderTasks'])
    ->name('admin.drivers.tasks.reorder');

Route::post('/emergency', [EmergencyController::class, 'emergencyBTN']);
Route::get('/check-emergency', [EmergencyController::class, 'checkEmergency']);
Route::post('/clear-emergency', [EmergencyController::class, 'clearEmergency']);

Route::get('/test-fcm/{driver_id}', function ($driver_id) {
    $driver = \App\Models\Driver::find($driver_id);
    if (!$driver) return 'Driver not found';
    if (!$driver->fcm_token) return 'Driver has no fcm_token';
    
    $fcm = new \App\Services\FcmService();
    $result = $fcm->sendNotification('Test Notification', 'This is a test push from backend', [$driver->fcm_token], null, 'test_action');
    
    return response()->json([
        'success' => true,
        'driver_id' => $driver->id,
        'fcm_response' => json_decode($result, true) ?? $result
    ]);
});