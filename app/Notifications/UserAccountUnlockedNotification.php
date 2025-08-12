<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserAccountUnlockedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected User $unlockedUser,
        protected User $admin,
        protected string $reason
    ) {}

    public function via(object $notifiable): array
    {
        if ($notifiable->id === $this->unlockedUser->id) {
            return ['mail'];
        }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable)
    {
        if ($notifiable->id === $this->unlockedUser->id) {
            return $this->buildMailMessage(
                'Your account has been unlocked',
                "Your account was unlocked by an administrator. Reason: {$this->reason}"
            );
        }

        return $this->buildMailMessage(
            'User account unlocked',
            "You successfully unlocked {$this->unlockedUser->email}'s account. Reason: {$this->reason}"
        );
    }

    public function toDatabase(object $notifiable)
    {
        return $this->formatData(
            'User account unlocked',
            "{$this->unlockedUser->email}'s account was unlocked by you. Reason: {$this->reason}"
        );
    }
}
