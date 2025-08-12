<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    public $connection;
    public $queue;
    public $delay = null;

    public function __construct()
    {
        $this->connection = config('queue.default', env('QUEUE_CONNECTION', 'sync'));
        $this->queue = 'default';
    }

    protected function formatData(string $title, string $body, array $extra = []): array
    {
        return array_merge([
            'title' => $title,
            'body'  => $body,
        ], $extra);
    }
}
