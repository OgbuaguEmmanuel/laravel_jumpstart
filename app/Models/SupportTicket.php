<?php

namespace App\Models;

use App\Enums\PermissionTypeEnum;
use App\Observers\SupportTicketObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(SupportTicketObserver::class)]
class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'subject', 'description', 'status', 'priority', 'updated_by'];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isUntreated(): bool
    {
        $hasAdminMessage = $this->messages()
            ->whereHas('user', fn ($q) => $q->hasPermission(PermissionTypeEnum::treatSupportTicket))
            ->exists();

        $adminUpdated = $this->updated_by && $this->updated_by !== $this->user_id
            && $this->updatedBy->hasPermissionTo(PermissionTypeEnum::treatSupportTicket);

        return ! $hasAdminMessage && ! $adminUpdated;
    }

    public function isTreated(): bool
    {
        return ! $this->isUntreated();
    }
}
