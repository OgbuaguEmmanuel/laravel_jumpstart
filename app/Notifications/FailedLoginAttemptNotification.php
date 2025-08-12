<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;

class FailedLoginAttemptNotification extends BaseNotification implements ShouldQueue
{
    protected $ip;
    protected $attempts;
    protected $maxAttempts;

    public function __construct(string $ip, int $attempts, int $maxAttempts)
    {
        $this->ip = $ip;
        $this->attempts = $attempts;
        $this->maxAttempts = $maxAttempts;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Failed Login Attempt')
            ->line("A failed login attempt was detected on your account.")
            ->line("IP Address: {$this->ip}")
            ->line("Attempt: {$this->attempts} of {$this->maxAttempts}")
            ->line('If this was not you, we recommend changing your password immediately.');
    }

    public function toDatabase(object $notifiable)
    {
        return $this->formatData(
            'Failed login attempt detected',
            "IP: {$this->ip}. Attempt {$this->attempts} of {$this->maxAttempts}."
        );
    }
}
