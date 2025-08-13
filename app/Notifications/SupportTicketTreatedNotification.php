<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SupportTicketTreatedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public $ticket) {}

    public function via($notifiable)
    {
        return ['mail','database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Support Ticket Has Been Treated')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your support ticket "' . $this->ticket->subject . '" has been updated by our support team.')
            ->line('Thank you for contacting us!');
    }

    public function toDatabase($notifiable)
    {
        return $this->formatData('Ticket Updated', 'Your support ticket has been treated by our team.' , [
            'ticket_id' => $this->ticket->id,
            'subject'   => $this->ticket->subject,
            'status'    => $this->ticket->status,
        ]);
    }
}
