<?php

namespace App\Traits;

use App\Models\Notification;

trait Helper
{
    public function createNotification($notifiable, $type, $data)
    {
        return Notification::create([
            'notifiable_id' => $notifiable->id,
            'notifiable_type' => get_class($notifiable),
            'type' => $type,
            'data' => $data,
        ]);
    }
}
