<?php

namespace App\Policies;

use App\Enums\PermissionTypeEnum;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): Response
    {
        return $user->id === $ticket->user_id || $user->hasPermissionTo(PermissionTypeEnum::viewSupportTicket) ?
            Response::allow() : Response::deny('You are not authorized to view this ticket',403);
    }

    public function update(User $user): Response
    {
        return $user->hasPermissionTo(PermissionTypeEnum::treatSupportTicket) ?
            Response::allow() : Response::deny('You are not authorized to treat this ticket',403);

    }

    public function delete(User $user, SupportTicket $ticket): Response
    {
        return $user->id === $ticket->user_id && $ticket->isUntreated() ?
            Response::allow() : Response::deny('You are not authorized to delete this ticket',403);
    }
}
