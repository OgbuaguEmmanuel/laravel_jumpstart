<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
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

    /**
     * Build a standard mail message.
     */
    protected function buildMailMessage(string $subject, string $line): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->line($line);
    }
}
