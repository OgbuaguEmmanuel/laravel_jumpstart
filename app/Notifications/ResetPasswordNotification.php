<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var string
     * @var string
     */
    public string $callbackUrl;
    public string $token;

    /**
     * Create a new notification instance.
     *
     * @param string $callbackUrl
     * @param string $token
     * @return void
     */
    public function __construct(string $callbackUrl, string $token)
    {
        $this->callbackUrl = $callbackUrl;
        $this->token = $token;
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
        $expires = config('auth.passwords.users.expire');
        return (new MailMessage())
            ->subject(Lang::get('Reset Password Notification'))
            ->greeting("Dear {$notifiable->full_name},")
            ->line(
                Lang::get('You are receiving this email because we received a password reset request for your account.')
            )
            ->action(Lang::get('Reset Password'), $this->getResetUrl())
            ->line(Lang::get('This password reset link will expire in :count minutes.', [
                'count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')
            ]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * @return string
     */
    protected function getResetUrl()
    {
        return "{$this->callbackUrl}?token={$this->token}";
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'callbackUrl' => $this->callbackUrl,
            'token' => $this->token,
        ];
    }
}
