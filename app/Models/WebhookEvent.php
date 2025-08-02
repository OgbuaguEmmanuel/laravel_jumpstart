<?php

namespace App\Models;

use App\Observers\WebhookEventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(WebhookEventObserver::class)]
class WebhookEvent extends Model
{
    protected $fillable = ['payment_gateway','log'];
}
