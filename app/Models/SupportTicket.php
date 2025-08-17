<?php

namespace App\Models;

use App\Enums\PermissionTypeEnum;
use App\Observers\SupportTicketObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(SupportTicketObserver::class)]
class SupportTicket extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = ['user_id', 'subject', 'description', 'status', 'priority', 'updated_by'];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class)
            ->select('id','first_name','last_name','email','is_active','is_locked','created_at');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')
            ->select('id','email','first_name','last_name');
    }

    public function isUntreated(): bool
    {
        $hasAdminMessage = $this->messages()
            ->whereHas('user.permissions', fn ($q) =>
                $q->where('name', PermissionTypeEnum::treatSupportTicket)
            )
            ->exists();

        $adminUpdated = $this->updated_by && $this->updated_by !== $this->user_id &&
            $this->updatedBy->hasPermissionTo(PermissionTypeEnum::treatSupportTicket);

        return ! $hasAdminMessage && ! $adminUpdated;
    }

    public function isTreated(): bool
    {
        return ! $this->isUntreated();
    }

    public function getActivitylogOptions(): LogOptions
    {
        $performedBy = Auth::user()->email;

        return LogOptions::defaults()
            // Log changes to all fillable attributes when the model is created/updated
            ->logFillable()
            // Only log attributes that actually changed (default, good for updates)
            ->logOnlyDirty()
            // Use the User model's class name as the log name for organization
            ->useLogName('SupportTicket')
            // Don't log if only these attributes change
            ->dontLogIfAttributesChangedOnly([
                'updated_at',
            ])
            // Don't create an empty log if nothing changed (default, good for updates)
            ->dontSubmitEmptyLogs()
            // Customize the description for different events
            ->setDescriptionForEvent(function (string $eventName) use ($performedBy) {
                if ($eventName === 'created') {
                    return 'New support ticket created: '.($this->subject ?? 'N/A');
                }

                return "Support ticket model has been {$eventName} by user with email $performedBy";
            });
    }
}
