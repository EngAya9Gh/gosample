<?php
$file = 'routes/api.php';
$content = file_get_contents($file);

// Insert the refresh route after login routes
$content = str_replace(
    "Route::post('driver/loginWithMobile','App\Http\Controllers\DriverController@loginWithMobile');",
    "Route::post('driver/loginWithMobile','App\Http\Controllers\DriverController@loginWithMobile');\nRoute::post('driver/refresh','App\Http\Controllers\DriverController@refresh');",
    $content
);

// Group 1: from driver/profile to driver/samples/valid/check
$group1_start = "Route::post('driver/profile','App\Http\Controllers\DriverController@profile');";
$group1_end = "Route::post('driver/samples/valid/check','App\Http\Controllers\SampleController@getConfirmedSamplesPerDriverId');";
$pos1 = strpos($content, $group1_start);
$pos2 = strpos($content, $group1_end) + strlen($group1_end);

$group1_content = substr($content, $pos1, $pos2 - $pos1);
$content = substr_replace($content, "Route::middleware('auth:drivers')->group(function () {\n    " . str_replace("\n", "\n    ", $group1_content) . "\n});", $pos1, $pos2 - $pos1);

// Group 2: from tasks/cache to swap/list/acceptall
$group2_start = "Route::any('tasks/cache','App\Http\Controllers\DriverController@getTasksFromCache');";
$group2_end = "Route::post('swap/list/acceptall','App\Http\Controllers\SwapController@acceptall');";
$pos3 = strpos($content, $group2_start);
$pos4 = strpos($content, $group2_end) + strlen($group2_end);

$group2_content = substr($content, $pos3, $pos4 - $pos3);
$content = substr_replace($content, "Route::middleware('auth:drivers')->group(function () {\n    " . str_replace("\n", "\n    ", $group2_content) . "\n});", $pos3, $pos4 - $pos3);

file_put_contents($file, $content);
echo "Updated routes/api.php successfully.\n";
