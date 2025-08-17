<?php

namespace App\Models;

use App\Enums\ActivityLogTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use CausesActivity, LogsActivity;

    protected $fillable = ['key', 'value', 'type'];

    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
            $this->attributes['type'] = 'json';
        } elseif (is_bool($value)) {
            $this->attributes['value'] = $value ? '1' : '0';
            $this->attributes['type'] = 'boolean';
        } elseif (is_int($value)) {
            $this->attributes['value'] = $value;
            $this->attributes['type'] = 'integer';
        } else {
            $this->attributes['value'] = $value;
            $this->attributes['type'] = 'string';
        }
    }

    public function getRouteKeyName()
    {
        return 'key';
    }

    public function getActivitylogOptions(): LogOptions
    {
        $performedBy = Auth::user()?->email;

        return LogOptions::defaults()
            // Log changes to all fillable attributes when the model is created/updated
            ->logFillable()
            // Only log attributes that actually changed (default, good for updates)
            ->logOnlyDirty()
            // Use the User model's class name as the log name for organization
            ->useLogName(ActivityLogTypeEnum::settingModel)
            // Don't log if only these attributes change
            ->dontLogIfAttributesChangedOnly([
                'updated_at',
            ])
            // Don't create an empty log if nothing changed (default, good for updates)
            ->dontSubmitEmptyLogs()
            // Customize the description for different events
            ->setDescriptionForEvent(function (string $eventName) use ($performedBy) {
                if ($eventName === 'created') {
                    return 'New setting created: '.($this->key ?? 'N/A');
                }

                return "Setting model has been {$eventName} by user with email $performedBy";
            });
    }
}
