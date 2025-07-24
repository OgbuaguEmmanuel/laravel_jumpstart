<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name','last_name',
        'email','phone_number',
        'password',
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
}
