<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginAlertNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public string $provider;

    public string $ip;

    public ?string $browser;

    public ?string $location;

    /**
     * @param  string  $provider  e.g. 'Google', 'Email/Password', '2FA'
     * @param  string|null  $location  (optional, if you use IP-to-location lookup)
     */
    public function __construct(string $provider, string $ip, ?string $browser = null, ?string $location = null)
    {
        $this->provider = $provider;
        $this->ip = $ip;
        $this->browser = $browser;
        $this->location = $location;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $loginMethod = $this->provider;

        return (new MailMessage)
            ->subject("New Login to Your {$appName} Account")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('We noticed a new login to your account.')
            ->line("**Login Method:** {$loginMethod}")
            ->line("**IP Address:** {$this->ip}")
            ->lineIf($this->browser, "**Browser/Device:** {$this->browser}")
            ->lineIf($this->location, "**Location:** {$this->location}")
            ->line('If this was you, you can safely ignore this message.')
            ->line("If this wasn't you, please reset your password immediately and contact support.");
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->formatData(
            'New Login Detected',
            "A new login to your account was detected via {$this->provider} from IP {$this->ip}".
            ($this->location ? " ({$this->location})" : '').'.'
        );
    }
}
