const fs = require('fs');
const file = 'routes/web.php';
let content = fs.readFileSync(file, 'utf8');

// Remove the new routes from the bottom
content = content.replace(
    /    Route::get\('admin\/scheduled-tasks', \[\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'index'\]\)->name\('admin\.scheduled-tasks\.index'\);\n    Route::get\('admin\/scheduled-tasks\/create', \[\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'create'\]\)->name\('admin\.scheduled-tasks\.create'\);\n    Route::post\('admin\/scheduled-tasks', \[\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'store'\]\)->name\('admin\.scheduled-tasks\.store'\);\n    Route::put\('admin\/scheduled-tasks\/{scheduledTask}', \[\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'update'\]\)->name\('admin\.scheduled-tasks\.update'\);\n    Route::delete\('admin\/scheduled-tasks\/{scheduledTask}', \[\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'destroy'\]\)->name\('admin\.scheduled-tasks\.destroy'\);\n    Route::get\('admin\/scheduled-tasks\/{scheduledTask}', \[\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'show'\]\)->name\('admin\.scheduled-tasks\.show'\);\n/, 
    ''
);

// Add them BEFORE the legacy resource route
const newRoutes = `    // --- SPA Scheduled Tasks Overrides ---
    Route::get('scheduled-tasks', [\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'index'])->name('scheduled-tasks.index');
    Route::get('scheduled-tasks/create', [\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'create'])->name('scheduled-tasks.create');
    Route::post('scheduled-tasks', [\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'store'])->name('scheduled-tasks.store');
    Route::put('scheduled-tasks/{scheduledTask}', [\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'update'])->name('scheduled-tasks.update');
    Route::delete('scheduled-tasks/{scheduledTask}', [\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'destroy'])->name('scheduled-tasks.destroy');
    Route::get('scheduled-tasks/{scheduledTask}', [\\App\\Http\\Controllers\\App\\ScheduledTasksController::class, 'show'])->name('scheduled-tasks.show');

    Route::resource('scheduled-tasks', 'ScheduledTaskController');`;

content = content.replace("    Route::resource('scheduled-tasks', 'ScheduledTaskController');", newRoutes);

fs.writeFileSync(file, content);
console.log("Patched web.php");
