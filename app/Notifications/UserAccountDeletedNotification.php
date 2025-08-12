<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;

class UserAccountDeletedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $deletedUser;
    public $admin;

    public function __construct(User $deletedUser, User $admin)
    {
        $this->deletedUser = $deletedUser;
        $this->admin = $admin;
    }

    public function via(object $notifiable): array
    {
        // Notifying the deleted user: email only
        if ($notifiable->id === $this->deletedUser->id) {
            return ['mail'];
        }

        // Notifying the admin: email and database
        if ($notifiable->id === $this->admin->id) {
            return ['mail', 'database'];
        }

        return [];
    }

    public function toMail(object $notifiable)
    {
        // Email for the deleted user
        if ($notifiable->id === $this->deletedUser->id) {
            return (new MailMessage)
                ->subject('Your account has been deleted')
                ->line("Hello {$this->deletedUser->name},")
                ->line('Your account has been deleted by an administrator.')
                ->line('If you believe this was a mistake, please contact support.');
        }

        // Email for the admin
        if ($notifiable->id === $this->admin->id) {
            return (new MailMessage)
                ->subject('User account deleted')
                ->line("You have deleted the account of {$this->deletedUser->email}.")
                ->line('This action has been logged for auditing.');
        }
    }

    public function toDatabase($notifiable)
    {
        // Only for admin
        return $this->formatData(
            'User Account Deleted',
            "You deleted the account of {$this->deletedUser->email}.",
            [
                'deleted_user_id' => $this->deletedUser->id,
                'deleted_user_email' => $this->deletedUser->email,
                'deleted_by' => $this->admin->email,
                'ip_address' => request()->ip(),
            ]
        );
    }
}
