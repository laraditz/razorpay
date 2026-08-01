<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $table = 'razorpay_api_logs';

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
}
