<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserStatusToggledNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $toggledUser,
        public $admin,
        public $statusText,
        public $reason
    ) {}

    public function via(object $notifiable): array
    {
        if ($notifiable->id === $this->toggledUser->id) {
            return ['mail'];
        }

        if ($notifiable->id === $this->admin->id) {
            return ['mail', 'database'];
        }

        return [];
    }

    public function toMail(object $notifiable)
    {
        if ($notifiable->id === $this->toggledUser->id) {
            return (new MailMessage)
                ->subject("Your account has been {$this->statusText}")
                ->line("Your account was {$this->statusText} by an administrator.")
                ->line("Reason: {$this->reason}")
                ->line('If you believe this is an error, please contact support.');
        }

        if ($notifiable->id === $this->admin->id) {
            return (new MailMessage)
                ->subject("User account {$this->statusText}")
                ->line("You {$this->statusText} the account of {$this->toggledUser->email}.")
                ->line("Reason: {$this->reason}");
        }
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->formatData(
            "User {$this->statusText} successfully",
            "{$this->toggledUser->email} was {$this->statusText} by you. Reason: {$this->reason}"
        );
    }
}
