<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Razorpay\Enums\OrderStatus;

class RazorpayOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'razorpay_id',
        'payment_id',
        'status',
        'amount',
        'amount_paid',
        'amount_due',
        'currency',
        'receipt',
        'attempts',
        'notes',
        'raw_response',
        'paid_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'amount' => 'integer',
        'amount_paid' => 'integer',
        'amount_due' => 'integer',
        'attempts' => 'integer',
        'notes' => 'array',
        'raw_response' => 'array',
        'paid_at' => 'datetime',
    ];
}
