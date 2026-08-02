<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Razorpay\Enums\SettlementStatus;

class RazorpaySettlement extends Model
{
    protected $fillable = [
        'razorpay_id',
        'amount',
        'fees',
        'tax',
        'utr',
        'status',
        'settled_at',
        'raw_response',
    ];

    protected $casts = [
        'status' => SettlementStatus::class,
        'amount' => 'integer',
        'fees' => 'integer',
        'tax' => 'integer',
        'settled_at' => 'datetime',
        'raw_response' => 'array',
    ];
}
