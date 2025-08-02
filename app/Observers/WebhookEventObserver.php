<?php

namespace App\Observers;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookEvent;

class WebhookEventObserver
{
    /**
     * Handle the WebhookEvent "created" event.
     */
    public function created(WebhookEvent $webhookEvent): void
    {
        $event = json_decode($webhookEvent->log);
        if (isset($event->data->reference, $event->data->authorization)) {
            ProcessWebhookJob::dispatch(
                $event->data->reference, $event->data->authorization,
                $webhookEvent->payment_gateway
            );
        }
    }

    /**
     * Handle the WebhookEvent "updated" event.
     */
    public function updated(WebhookEvent $webhookEvent): void
    {
        //
    }

    /**
     * Handle the WebhookEvent "deleted" event.
     */
    public function deleted(WebhookEvent $webhookEvent): void
    {
        //
    }

    /**
     * Handle the WebhookEvent "restored" event.
     */
    public function restored(WebhookEvent $webhookEvent): void
    {
        //
    }

    /**
     * Handle the WebhookEvent "force deleted" event.
     */
    public function forceDeleted(WebhookEvent $webhookEvent): void
    {
        //
    }
}
