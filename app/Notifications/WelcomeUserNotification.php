<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeUserNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public string $callbackUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
        $this->afterCommit();
    }

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the email message.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject("Welcome to {$appName}")
            ->greeting("Dear {$notifiable->full_name},")
            ->line("Welcome to {$appName}! We're excited to have you on board.")
            ->line('Click the button below to log in and start exploring:')
            ->action('Go to Dashboard', $this->callbackUrl)
            ->line('If you have any questions, feel free to reach out to our support team.');
    }
}
