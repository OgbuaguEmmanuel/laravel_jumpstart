<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificationPolicy
{
    public function view(User $user, Notification $notification): Response
    {
        return $user->id === $notification->user_id
            ? Response::allow()
            : Response::deny('Unauthorized to view this notification.', 403);
    }

    public function markRead(User $user, Notification $notification): Response
    {
        return $user->id === $notification->notifiable_id
            ? Response::allow()
            : Response::deny('Unauthorized to mark this notification as read.', 403);
    }

    public function markUnread(User $user, Notification $notification): Response
    {
        return $user->id === $notification->user_id
            ? Response::allow()
            : Response::deny('Unauthorized to mark this notification as unread.', 403);
    }

    public function destroy(User $user, Notification $notification): Response
    {
        return $user->id === $notification->user_id
            ? Response::allow()
            : Response::deny('Unauthorized to delete this notification.', 403);
    }

}
