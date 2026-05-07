<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceDelayNotification extends Notification
{
    use Queueable;

    protected $driver;
    protected $delayMinutes;

    public function __construct($driver, $delayMinutes)
    {
        $this->driver = $driver;
        $this->delayMinutes = $delayMinutes;
    }

    public function via($notifiable)
    {
        return ['database']; // Internal system notification
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'تأخير في الدوام - Attendance Delay',
            'message' => "السائق {$this->driver->name} تأخر عن موعد الدوام بـ {$this->delayMinutes} دقيقة.",
            'driver_id' => $this->driver->id,
            'delay_minutes' => $this->delayMinutes,
            'type' => 'attendance_delay'
        ];
    }
}
