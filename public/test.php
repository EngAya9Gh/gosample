<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$q = App\Models\Task::withoutGlobalScope('active')->where('is_unused', 1)->whereHas('driver', function($q) { $q->where('status', 1); })->select('tasks.*');
echo "Count: " . $q->count() . "\n";
print_r($q->get()->pluck('id')->toArray());
