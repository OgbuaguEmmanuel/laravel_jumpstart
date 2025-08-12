<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordChangedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    protected string $ip;
    protected string $time;

    public function __construct(string $ip, string $time)
    {
        $this->ip = $ip;
        $this->time = $time;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Password Has Been Changed')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your password was successfully changed.')
            ->line("**IP Address:** {$this->ip}")
            ->line("**Time:** {$this->time}")
            ->line('If you did not make this change, please reset your password immediately.');
    }
}
