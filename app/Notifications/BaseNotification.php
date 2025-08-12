<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    protected function formatData(string $title, string $body, array $extra = []): array
    {
        return array_merge([
            'title' => $title,
            'body'  => $body,
        ], $extra);
    }
}
