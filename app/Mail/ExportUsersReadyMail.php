<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportUsersReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $downloadUrl;

    public $fileName;

    public $filePath;

    public $mimeType;

    public function __construct($downloadUrl, $fileName, $filePath = null, $mimeType = null)
    {
        $this->downloadUrl = $downloadUrl;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
    }

    public function build()
    {
        $mail = $this->subject('Your user export is ready');

        if ($this->filePath && file_exists($this->filePath)) {
            $mail->attach($this->filePath, [
                'as' => $this->fileName,
                'mime' => $this->mimeType,
            ])->markdown('mail.export-users-ready-mail', [
                'downloadUrl' => null,
                'fileName' => $this->fileName,
            ]);
        } else {
            $mail->markdown('mail.export-users-ready-mail', [
                'downloadUrl' => $this->downloadUrl,
                'fileName' => $this->fileName,
            ]);
        }

        return $mail;
    }
}
