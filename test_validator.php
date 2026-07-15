<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$data = ['task_id' => 773843, 'container_id' => '669-container', 'bag_code' => '2149104547-bag'];
$validator = Validator::make($data, ['task_id' => 'required', 'bag_code' => 'required', 'container_id' => 'required']);
var_dump($validator->fails());
var_dump($validator->messages()->toArray());

