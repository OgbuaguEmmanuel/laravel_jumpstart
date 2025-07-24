<?php

namespace App\Listeners;

use App\Events\ResetPasswordEvent;
use App\Notifications\SuccessfulPasswordResetNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PasswordResetListener implements ShouldQueue
{
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
    public function handle(ResetPasswordEvent $event): void
    {
        $event->user->notify(new SuccessfulPasswordResetNotification($event->callbackContactUrl));
    }
}
