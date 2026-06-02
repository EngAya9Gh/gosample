<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$driver = \App\Models\Driver::first();
echo "Driver ID: " . $driver->id . "\n";
echo "Token: " . $driver->fcm_token . "\n";

if (!$driver->fcm_token) {
    echo "NO FCM TOKEN FOR THIS DRIVER!\n";
    exit;
}

$fcm = new \App\Services\FcmService();
$response = $fcm->sendNotification('Test Title', 'Test Body', [$driver->fcm_token], null, 'test_action');
echo "FCM Response: " . $response . "\n";
