<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 1. Authenticate as Driver ID = 1 to get a token
$driver = \App\Models\Driver::find(1);
$token = auth()->guard('drivers')->login($driver);

// 2. Make a request to the protected API, but try to forge driver_id = 999
$request = Illuminate\Http\Request::create('/api/driver/tasks', 'POST', [
    'driver_id' => 999, // Fake ID
    'status' => 'NEW'
]);
$request->headers->set('Accept', 'application/json');
$request->headers->set('Authorization', 'Bearer ' . $token);

// We can dump the request driver_id inside a quick test route, or just see the response
// If it queries tasks for driver 999, it might return empty or error.
// To truly verify, let's just dump the request's driver_id right after handle
$response = $kernel->handle($request);
echo "Requested Driver ID (from app): 999\n";
echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Body (Snipped): " . substr($response->getContent(), 0, 300) . "...\n";

