<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var string
     */
    public string $callbackUrl;

     /**
     * @var string
     */
    public string $token;

    /**
     * Create a new notification instance.
     *
     * @param string $loginCallbackUrl
     * @return void
     */
    public function __construct(string $callbackUrl, string $token)
    {
        $this->callbackUrl = $callbackUrl;
        $this->token = $token;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage())
            ->subject("Welcome to " . $appName)
            ->greeting("Dear $notifiable->full_name")
            ->line(
                Lang::get('An account has been created on your behalf on our platform.')
            )
            ->line(
                Lang::get('Kindly click on the link below to reset your password to start using our platform.')
            )
            ->action($appName . ' Reset Password', $this->getResetPasswordUrl());
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * @return string
     */
    protected function getResetPasswordUrl(): string
    {
        return "{$this->callbackUrl}?token={$this->token}";
    }
}
