<?php

namespace App\Models;

use App\Enums\ActivityLogTypeEnum;
use App\Enums\MediaTypeEnum;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
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
        'email','phone_number','force_password_reset',
        'password','provider_name', 'locked_until',
        'provider_id', 'avatar', 'failed_attempts',
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
            'is_locked' => 'boolean',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'locked_until' => 'datetime',
            'force_password_reset' => 'boolean'
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
        $callbackUrl = request('callbackUrl', config('frontend.url'));

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
            ->useLogName(ActivityLogTypeEnum::UserModel)
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

    public function activateAccount()
    {
        $this->is_active = true;
        $this->activated_at = now();
        $this->save();
    }

    /**
     * Scope a query to only include locked users.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLockedUsers(Builder $query): Builder
    {
        return $query->whereNotNull('locked_until')->where('locked_until', '>', now());
    }

    /**
     * Check if the user account is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_attempts');
    }

    /**
     * Reset failed login attempts and unlock the account.
     */
    public function resetFailedAttempts(): void
    {
        $this->failed_attempts = 0;
        $this->locked_until = null;
        $this->is_Locked = false;
        $this->status_reason = null;
        $this->save();
    }

    /**
     * Lock the user account for a specified duration.
     *
     * @param int $durationMinutes The duration in minutes to lock the account.
     * @param string|null $reason The reason for locking.
     */
    public function lockAccount(int $durationMinutes, ?string $reason = null): void
    {
        $this->locked_until = now()->addMinutes($durationMinutes);
        $this->failed_attempts = 0; // Reset attempts after locking
        $this->is_Locked = true;
        $this->status_reason = $reason;
        $this->save();
    }

    /**
     * Unlock the user account immediately.
     *
     * @param string|null $reason The reason for unlocking.
     */
    public function unlockAccount(?string $reason = null): void
    {
        $this->resetFailedAttempts();
        $this->status_reason = $reason;
        $this->save();
    }

    public function forcePasswordReset()
    {
        $this->force_password_reset = true;
        $this->save();
    }

    /**
     * Send welcome notification to new user
     * @return void
     */
    public function sendWelcomeNotification(): void
    {
        $callbackUrl = request('callbackUrl', config('frontend.url'));
        $token = Password::broker('users')->createToken($this);

        $this->notify(new WelcomeNotification($callbackUrl, $token));
    }

    public function sendEmailVerificationNotification()
    {
        $callbackUrl = request('callbackUrl', config('frontend.url'));

        $this->notify(new VerifyEmailNotification($callbackUrl));
    }

    /**
     * Register media collections.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaTypeEnum::ProfilePicture)
            ->acceptsMimeTypes(['image/png', 'image/jpeg'])
            ->singleFile();
    }
}
