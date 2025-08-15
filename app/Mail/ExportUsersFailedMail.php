<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExportUsersFailedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $fileName;

    public string $name;

    public string $errorMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $fileName, $errorMessage = null)
    {
        $this->fileName = $fileName;
        $this->name = $name;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'User Export Failed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.export-users-failed-mail',
            with: [
                'fileName' => $this->fileName,
                'fullName' => $this->name,
                'errorMessage' => $this->errorMessage,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
