<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/driver/tasks', 'POST', [
    'driver_id' => 1,
    'status' => 'NEW'
]);
$request->headers->set('Accept', 'application/json');
// Do NOT set any authorization header!
$response = $kernel->handle($request);
echo "Status Code: " . $response->getStatusCode() . "\n";
echo substr($response->getContent(), 0, 500) . "...\n";
