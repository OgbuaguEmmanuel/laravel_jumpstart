<?php

namespace App\Jobs;

use App\Mail\ExportUsersFailedMail;
use App\Mail\ExportUsersReadyMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\MimeTypes;

class SendExportReadyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var \App\Models\User */
    public $admin;

    public string $filePath;

    public string $fileName;

    public function __construct($admin, string $filePath, string $fileName)
    {
        $this->admin = $admin;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function handle(): void
    {
        if (! Storage::disk('local')->exists($this->filePath)) {
            Log::error('Export file missing', [
                'filePath' => $this->filePath,
                'admin_id' => $this->admin->id,
            ]);

            Mail::to($this->admin->email)->send(
                new ExportUsersFailedMail(
                    $this->admin->full_name,
                    $this->fileName,
                )
            );

            return;
        }

        $fullPath = storage_path('app/private/'.$this->filePath);
        $fileSize = filesize($fullPath);
        $maxAttachmentSize = 5 * 1024 * 1024; // 5MB

        if ($fileSize <= $maxAttachmentSize) {
            $mimeType = MimeTypes::getDefault()->guessMimeType($fullPath) ?? 'application/octet-stream';

            Mail::to($this->admin->email)->send(
                new ExportUsersReadyMail(
                    null,
                    $this->fileName,
                    $fullPath,
                    $mimeType
                )
            );
        } else {
            $downloadUrl = URL::temporarySignedRoute(
                'exports.download',
                now()->addHours(48),
                ['file' => basename($this->fileName), 'owner' => $this->admin->id],
            );

            Mail::to($this->admin->email)->send(
                new ExportUsersReadyMail(
                    $downloadUrl,
                    $this->fileName
                )
            );
        }
    }
}
