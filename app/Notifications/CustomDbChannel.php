<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CustomDbChannel
{

    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);

        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,


            'driver_id' => $data['task']['driver_id'] ?? $data['driver_id'] ?? null,
            'task_id' => $data['task']['id'] ?? $data['task_id'] ?? null,
            'billing_client' => $data['task']['billing_client'] ?? null,
            'from_location' => $data['task']['from_location'] ?? null,
            'to_location' => $data['task']['to_location'] ?? null,

            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
        ]);
    }

}
