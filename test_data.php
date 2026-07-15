<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.connections.mysql.host' => '127.0.0.1']);
config(['database.connections.mysql.port' => '3312']);

$req = request();
$req->merge(['page'=>1, 'pageSize'=>25]);

$query = \App\Models\ScheduledTask::with(['from_location', 'to_location', 'client', 'driver'])
    ->whereNull('parent_id')
    ->select('scheduled_tasks.*');

echo "Total Count: " . $query->count() . "\n";
$rows = $query->offset(0)->limit(25)->get();
echo "Rows returned: " . $rows->count() . "\n";

if ($rows->count() > 0) {
    echo "First Row ID: " . $rows[0]->id . "\n";
    echo "First Row client_id: " . $rows[0]->client_id . "\n";
}
