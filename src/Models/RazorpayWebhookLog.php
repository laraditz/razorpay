<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Razorpay\Enums\WebhookLogStatus;

class RazorpayWebhookLog extends Model
{
    protected $fillable = [
        'event_type',
        'status',
        'payload',
        'reference_id',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'status' => WebhookLogStatus::class,
    ];
}
