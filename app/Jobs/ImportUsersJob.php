<?php

namespace App\Jobs;

use App\Imports\UsersImport;
use App\Mail\ImportUsersReportMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $admin;

    public $filePath;

    public $pathForDelete;

    public function __construct($admin, $filePath, $pathForDelete)
    {
        $this->admin = $admin;
        $this->filePath = $filePath;
        $this->pathForDelete = $pathForDelete;
    }

    public function handle()
    {
        $import = new UsersImport($this->admin->id);
        Excel::import($import, $this->filePath);

        Storage::disk('local')->delete($this->pathForDelete);

        Mail::to($this->admin->email)->send(
            new ImportUsersReportMail($import->failures())
        );
    }
}
