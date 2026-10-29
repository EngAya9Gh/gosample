<?php

// Push notification texts sent to drivers (see Driver::pushNotification)
return [
    'new_task' => [
        'title' => 'New task #:task',
        'body' => 'You have been assigned a new task from :from to :to. Tap to view details.',
    ],
    'new_task_generic' => [
        'title' => 'New task',
        'body' => 'You have been assigned a new task. Tap to view details.',
    ],
    'task_accepted' => [
        'title' => 'Task #:task accepted',
        'body' => 'Your acceptance of the task from :from to :to has been confirmed.',
    ],
    'task_started' => [
        'title' => 'Task #:task started',
        'body' => 'Task start recorded. Please head to :from to collect the samples.',
    ],
    'pickup_delayed' => [
        'title' => 'Late pickup – task #:task',
        'body' => 'The samples were collected from :from after the scheduled time, and the task has been marked as delayed.',
    ],
    'dropoff_delayed' => [
        'title' => 'Late drop-off – task #:task',
        'body' => 'The samples were delivered to :to after the scheduled time, and the task has been marked as delayed.',
    ],
    'placement_delayed' => [
        'title' => 'Place samples in freezer – task #:task',
        'body' => 'More than 15 minutes have passed since collection and the samples are not in the freezer yet. Please place them now to keep them safe.',
    ],
    'delivery_delayed' => [
        'title' => 'Deliver samples now – task #:task',
        'body' => 'More than 15 minutes have passed since the samples were taken out of the freezer. Please deliver them to :to now.',
    ],
    'attendance_late' => [
        'title' => 'Late for your shift',
        'body' => 'You are :minutes minutes past your shift start time. Please check in now.',
    ],
];
