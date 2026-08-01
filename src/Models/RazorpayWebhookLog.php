<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Laraditz\Razorpay\Enums\WebhookLogStatus;

class RazorpayWebhookLog extends Model
{
    use Prunable;

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

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(config('razorpay.webhook_log_retention_days', 30)));
    }
}
