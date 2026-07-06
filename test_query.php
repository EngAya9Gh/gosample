<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.connections.mysql.host' => '127.0.0.1']);
config(['database.connections.mysql.port' => '3312']);

$req = request();
$req->headers->set('X-Inertia', 'true');
$req->merge(['page'=>1, 'pageSize'=>25]);
$c = app()->make(App\Http\Controllers\App\ScheduledTasksController::class);
$res = $c->index($req);

echo "\n--- OUTPUT ---\n";
echo json_encode($res->toResponse($req)->getContent());
echo "\n";
