<?php

namespace App\Jobs;

use App\Exports\UsersExport;
use App\Mail\ExportUsersFailedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Excel as ExcelWriter;

class ExportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $admin;

    public $type;

    public $limit;

    public $offset;

    public $year;

    public function __construct($admin, $type, $limit, $offset, $year)
    {
        $this->admin = $admin;
        $this->type = $type;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->year = $year;
    }

    public function handle(): void
    {
        $extension = $this->type === 'csv' ? 'csv' : 'xlsx';
        $writerType = $this->type === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;

        $relativeDir = 'exports';
        $fileName = 'users_export_'.now()->format('Y_m_d_His').'.'.$extension;
        $filePath = $relativeDir.'/'.$fileName;

        (new UsersExport($this->limit, $this->offset, $this->year))
            ->store($filePath, 'local', $writerType);

        DeleteExportFileJob::dispatch($filePath)->delay(now()->addHours(48));

        dispatch(new SendExportReadyJob($this->admin, $filePath, $fileName));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('User export job failed', [
            'file' => $this->fileName ?? '(not generated)',
            'error' => $exception->getMessage(),
        ]);

        Mail::to($this->admin->email)->send(
            new ExportUsersFailedMail(
                $this->admin->full_name,
                $this->fileName ?? '(not generated)',
                $exception->getMessage()
            )
        );
    }
}
