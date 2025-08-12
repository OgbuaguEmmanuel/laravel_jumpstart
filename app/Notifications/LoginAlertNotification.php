<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginAlertNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected string $ip;
    protected string $userAgent;
    protected string $time;

    public function __construct(string $ip, string $userAgent, string $time)
    {
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->time = $time;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Login to Your Account')
            ->greeting("Hello {$notifiable->name},")
            ->line('A new login to your account was detected.')
            ->line("**IP Address:** {$this->ip}")
            ->line("**Device/Browser:** {$this->userAgent}")
            ->line("**Time:** {$this->time}")
            ->line('If this was not you, please reset your password immediately.');
    }
}
