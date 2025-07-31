<?php

namespace App\Listeners;

use App\Enums\ActivityLogTypeEnum;
use App\Enums\ToggleStatusReasonEnum;
use App\Models\User;
use App\Traits\AuthHelpers;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogFailedLoginAttempt implements ShouldQueue
{
    use AuthHelpers;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        if ($event->user instanceof User) {
            $user = $event->user;
        } else {
            $user = $this->getUserByEmail($event->credentials['email']);
        }

        if ($user) {
            $user->incrementFailedAttempts();

            $maxAttempts = config('auth.lockout.max_attempts');
            $lockoutDuration = config('auth.lockout.duration'); // in minutes

            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy(null)
                ->performedOn($user)
                ->withProperties([
                    'email_attempted' => $event->credentials['email'] ?? 'N/A',
                    'ip_address' => request()->ip(),
                    'failed_attempts_count' => $user->failed_attempts,
                    'max_attempts_threshold' => $maxAttempts,
                ])
                ->log("Login failed for user '{$user->email}'. Attempt #{$user->failed_attempts}.");

            if ($user->failed_attempts >= $maxAttempts && !$user->isLocked()) {
                $user->lockAccount($lockoutDuration, ToggleStatusReasonEnum::FRAUDULENT_ACTIVITY);

                activity()
                    ->inLog(ActivityLogTypeEnum::Login)
                    ->causedBy(null)
                    ->performedOn($user)
                    ->withProperties([
                        'email' => $user->email,
                        'ip_address' => request()->ip(),
                        'lockout_duration_minutes' => $lockoutDuration,
                        'lockout_reason' => ToggleStatusReasonEnum::FRAUDULENT_ACTIVITY,
                    ])
                    ->log("User '{$user->email}' account locked for {$lockoutDuration} minutes due to multiple failed login attempts.");

                Log::warning("User '{$user->email}' account locked due to {$user->failed_attempts} failed login attempts.");
            }
        } else {
            activity()
                ->inLog(ActivityLogTypeEnum::Login)
                ->causedBy(null)
                ->withProperties([
                    'email_attempted' => $event->credentials['email'] ?? 'N/A',
                    'ip_address' => request()->ip(),
                ])
                ->log("Login failed for unknown user or invalid credentials.");
        }
    }
}
