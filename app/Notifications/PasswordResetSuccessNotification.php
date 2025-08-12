<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetSuccessNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public string $loginUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $loginUrl)
    {
        $this->loginUrl = $loginUrl;
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
            ->subject("Your {$appName} Password Was Successfully Reset")
            ->greeting("Dear {$notifiable->full_name},")
            ->line("This is a confirmation that your password for {$appName} has been successfully reset.")
            ->line("If you made this change, you can safely ignore this message.")
            ->line("If you did not request this change, please reset your password immediately and contact our support team.")
            ->action("Log In", $this->loginUrl)
            ->line("For security, we recommend enabling two-factor authentication to further protect your account.");
    }
}
