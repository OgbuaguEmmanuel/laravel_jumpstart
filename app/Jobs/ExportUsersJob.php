<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExportUsersReadyMail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\MimeTypes;

class ExportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $admin;
    public $type;

    public function __construct($admin, $type)
    {
        $this->admin = $admin;
        $this->type = $type;
    }

    public function handle()
    {
        $fileName = 'users_export_' . now()->format('Y_m_d_His') . '.' . $this->type;
        $filePath = 'exports/' . $fileName;

        Excel::store(new UsersExport, $filePath, 'local');

        $fullPath = storage_path('app/' . $filePath);
        $fileSize = filesize($fullPath); // size in bytes
        $maxAttachmentSize = 5 * 1024 * 1024; // 5 MB

        if ($fileSize <= $maxAttachmentSize) {
            // Send as attachment
            $mimeType = MimeTypes::getDefault()->guessMimeType($fullPath);
            Mail::to($this->admin->email)->send(
                new ExportUsersReadyMail(
                    null,
                    $fileName,
                    $fullPath,
                    $mimeType
                )
            );
        } else {
            // Send secure download link
            $downloadUrl = URL::temporarySignedRoute(
                'exports.download',  now()->addHours(48),
                ['file' => $fileName, 'owner' => $this->admin->id]
            );

            Mail::to($this->admin->email)->send(
                new ExportUsersReadyMail(
                    $downloadUrl,
                    $fileName
                )
            );
        }
    }
}
