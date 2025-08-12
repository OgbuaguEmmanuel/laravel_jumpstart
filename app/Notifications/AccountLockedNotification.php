<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;

class AccountLockedNotification extends BaseNotification implements ShouldQueue
{
    protected $duration;
    protected $ip;

    public function __construct(int $duration, string $ip)
    {
        $this->duration = $duration;
        $this->ip = $ip;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Account Locked')
            ->line("Your account has been locked due to multiple failed login attempts.")
            ->line("IP Address: {$this->ip}")
            ->line("Lockout Duration: {$this->duration} minutes")
            ->line("If this wasn't you, contact support immediately.");
    }

    public function toDatabase(object $notifiable)
    {
        return $this->formatData(
            'Account locked due to suspicious activity',
            "Your account was locked for {$this->duration} minutes. IP: {$this->ip}"
        );
    }
}
