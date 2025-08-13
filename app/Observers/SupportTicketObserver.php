<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Notifications\SupportTicketTreatedNotification;

class SupportTicketObserver
{
    public function updated(SupportTicket $ticket)
    {
        // Only notify if it was previously untreated and now treated
        if (!$ticket->getOriginal('treated') && $ticket->isTreated()) {
            $ticket->user->notify(new SupportTicketTreatedNotification($ticket));
        }
    }
}
