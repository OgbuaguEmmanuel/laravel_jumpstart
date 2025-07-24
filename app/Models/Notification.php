<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['notifiable_id', 'notifiable_type', 'type', 'data', 'read'];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

}
