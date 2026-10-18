<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionScheduleChanged extends Notification
{
    use Queueable;

    public function __construct(public readonly string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Mise à jour de votre emploi du temps',
            'message' => $this->message,
        ];
    }
}
