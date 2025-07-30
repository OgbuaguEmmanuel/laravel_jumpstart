<?php

namespace App\Models;

use App\Enums\ActivityLogType;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia, HasRoles;
    use LogsActivity, CausesActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name','last_name',
        'email','phone_number',
        'password','provider_name',
        'provider_id', 'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled_at' => 'datetime',
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $callbackUrl = request('callbackUrl', config('frontend.user.url'));

        $this->notify(new ResetPasswordNotification($callbackUrl, $token));
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return ucwords("{$this->first_name} {$this->last_name}");
    }

    // Accessor to decrypt the 2FA secret when accessed
    public function getTwoFactorSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setTwoFactorSecretAttribute($value)
    {
        $this->attributes['two_factor_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getTwoFactorRecoveryCodesAttribute($value)
    {
        return $value ? json_decode(Crypt::decryptString($value), true) : [];
    }

    public function setTwoFactorRecoveryCodesAttribute($value)
    {
        $this->attributes['two_factor_recovery_codes'] = $value ? Crypt::encryptString(json_encode($value)) : null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_enabled_at);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Log changes to all fillable attributes when the model is created/updated
            ->logFillable()
            // Only log attributes that actually changed (default, good for updates)
            ->logOnlyDirty()
            // Use the User model's class name as the log name for organization
            ->useLogName(ActivityLogType::UserModel)
            // Don't log if only these attributes change
            ->dontLogIfAttributesChangedOnly([
                'updated_at',
            ])
            // Don't create an empty log if nothing changed (default, good for updates)
            ->dontSubmitEmptyLogs()
            // Customize the description for different events
            ->setDescriptionForEvent(function(string $eventName) {
                if ($eventName === 'created') {
                    return "New user registered: " . ($this->email ?? 'N/A');
                }
                return "User model has been {$eventName}";
            });
    }

    public function actions()
    {
        return $this->hasMany(Activity::class, 'causer_id')->where('causer_type', User::class);
    }

        /**
     * Scope a query to filter users by active status.
     *
     * @param Builder $query
     * @param bool $isActive
     * @return Builder
     */
    public function scopeIsActive(Builder $query, bool $isActive = true): Builder
    {
        return $query->where('is_active', $isActive);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActiveUsers(Builder $query): Builder
    {
        return $query->isActive(true);
    }

    /**
     * Scope a query to only include deactivated users.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDeactivatedUsers(Builder $query): Builder
    {
        return $query->isActive(false);
    }

}
