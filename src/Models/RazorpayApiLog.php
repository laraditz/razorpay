<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class RazorpayApiLog extends Model
{
    use Prunable;

    protected $fillable = [
        'method',
        'endpoint',
        'reference_id',
        'request_payload',
        'response_payload',
        'http_status',
        'duration_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(config('razorpay.api_log_retention_days', 30)));
    }
}
