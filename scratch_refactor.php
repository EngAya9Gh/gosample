<?php

$file = __DIR__ . '/app/Http/Controllers/SampleController.php';
$content = file_get_contents($file);

$replacements = [
    "\'system error\'" => "__('messages.system_error')",
    "\"system error\"" => "__('messages.system_error')",
    "\'task is not found\'" => "__('messages.task_not_found')",
    "\'task not found\'" => "__('messages.task_not_found')",
    "\'location is not found\'" => "__('messages.location_not_found')",
    "\'task status is not valid\'" => "__('messages.task_status_not_valid')",
    "\'task status is not closed from driver\'" => "__('messages.task_status_not_closed_from_driver')",
    "\'container is not found\'" => "__('messages.container_not_found')",
    "\'bag already added to task\'" => "__('messages.bag_already_added_to_task')",
    "\'sample not found\'" => "__('messages.sample_not_found')",
    "\'sample is not under this task\'" => "__('messages.sample_not_under_this_task')",
    "\'sample is not existed\'" => "__('messages.sample_not_existed')",
    "\'please remove all bags\'" => "__('messages.please_remove_all_bags')",
    "\'cannot collect in this location\'" => "__('messages.cannot_collect_in_this_location')",
    "\'عذراً، أنت بعيد جداً عن الموقع.\'" => "__('messages.far_from_location')",
    "\'عذراً، أنت بعيد جداً عن الموقع. يرجى الاقتراب أولاً.\'" => "__('messages.far_from_location_approach')",
    "\'you cannot close task in this location\'" => "__('messages.cannot_close_task_in_this_location')",
    "\'tasks are not found\'" => "__('messages.tasks_not_found')",
    "\'task type is not correct\'" => "__('messages.task_type_not_correct')",
    "\'sample type is not equal to container type\'" => "__('messages.sample_type_not_equal_to_container_type')",
    "\'sample count  is required\'" => "__('messages.sample_count_is_required')",
    "\'please add all bags\'" => "__('messages.please_add_all_bags')",
    "\'no task available\'" => "__('messages.no_task_available')",
    "\'location is not valid\'" => "__('messages.location_not_valid')",
    "\'box count  is required\'" => "__('messages.box_count_is_required')",
    "\'sample already added to task\'" => "__('messages.sample_already_added_to_task')",
    "\'bag is not found\'" => "__('messages.bag_not_found')",
    "\'Please add car to driver\'" => "__('messages.please_add_car_to_driver')",

    // With concatenation
    "\'you cannot close task in this location for task \' . \$task->id" => "__('messages.cannot_close_task_in_this_location_for_task', ['task' => \$task->id])",
    "\'task status is not valid for task \' . \$task->id" => "__('messages.task_status_not_valid_for_task', ['task' => \$task->id])",
    "\'عذراً، أنت بعيد جداً عن الموقع لمهمة رقم \' . \$task_id" => "__('messages.far_from_location_for_task', ['task' => \$task_id])",
    "\'sample already confirmed by \'.\$sample->confirmed_by" => "__('messages.sample_already_confirmed_by', ['confirmed_by' => \$sample->confirmed_by])"
];

// un-escape single quotes for str_replace
$actual_replacements = [];
foreach ($replacements as $k => $v) {
    $key = str_replace("\'", "'", $k);
    $key = str_replace('\"', '"', $key);
    $actual_replacements[$key] = $v;
}

foreach ($actual_replacements as $old => $new) {
    $content = str_replace($old, $new, $content);
}

file_put_contents($file, $content);
echo "Replaced strings in SampleController.php\n";
