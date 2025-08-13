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
use Maatwebsite\Excel\Facades\Excel;

class ImportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $admin;
    public $filePath;

    public function __construct($admin, $filePath)
    {
        $this->admin = $admin;
        $this->filePath = $filePath;
    }

    public function handle()
    {
        $import = new UsersImport($this->admin);
        Excel::import($import, storage_path('app/' . $this->filePath));

        Mail::to($this->admin->email)->send(
            new ImportUsersReportMail($import->failures())
        );
    }
}
