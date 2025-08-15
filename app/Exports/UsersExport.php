<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

class UsersExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    protected $limit;

    protected $offset;

    protected $year;

    public function __construct($limit = null, $offset = null, $year = null)
    {
        $this->limit = $limit !== null ? (int) $limit : null;
        $this->offset = $offset !== null ? (int) $offset : null;
        $this->year = $year !== null ? (int) $year : null;
    }

    public function query()
    {
        return User::query()->with('roles:id,name')
            ->select('id', 'first_name', 'last_name', 'email', 'created_at')
            ->when($this->year !== null, fn ($q) => $q->whereYear('created_at', $this->year))
            ->when($this->offset !== null, fn ($q) => $q->skip($this->offset))
            ->when($this->limit !== null, fn ($q) => $q->take($this->limit))
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['First Name', 'Last Name', 'Email', 'Role', 'Created At'];
    }

    public function map($user): array
    {
        return [
            $user->first_name,
            $user->last_name,
            $user->email,
            optional($user->roles->first())->name,
            Date::dateTimeToExcel($user->created_at),
        ];
    }

    public function chunkSize(): int
    {
        return 5000; // Export 5k rows at a time
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Users export failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'limit' => $this->limit,
            'offset' => $this->offset,
            'year' => $this->year,
        ]);

        // If running synchronously (not in a queued job)
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            throw new \RuntimeException('The export failed. Please try again later.');
        }
    }
}
