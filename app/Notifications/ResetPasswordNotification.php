<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public string $callbackUrl;

    public string $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $callbackUrl, string $token)
    {
        $this->callbackUrl = $callbackUrl;
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $expires = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->greeting("Dear {$notifiable->full_name},")
            ->line(
                Lang::get('You are receiving this email because we received a password reset request for your account.')
            )
            ->action(Lang::get('Reset Password'), $this->getResetUrl())
            ->line(Lang::get('This password reset link will expire in :count minutes.', [
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
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
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'callbackUrl' => $this->callbackUrl,
            'token' => $this->token,
        ];
    }

    public function toDatabase($notifiable)
    {
        return $this->formatData(
            'Password Reset Request',
            'You requested a password reset. Click the link to proceed.',
            [
                'callbackUrl' => $this->callbackUrl,
                'token' => $this->token,
            ]
        );
    }
}
