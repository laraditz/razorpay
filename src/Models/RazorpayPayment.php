<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Razorpay\Enums\PaymentStatus;

class RazorpayPayment extends Model
{
    protected $fillable = [
        'razorpay_id',
        'order_id',
        'status',
        'method',
        'amount',
        'amount_refunded',
        'currency',
        'captured',
        'description',
        'email',
        'contact',
        'notes',
        'fee',
        'tax',
        'error_code',
        'error_description',
        'raw_response',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'amount_refunded' => 'integer',
        'captured' => 'boolean',
        'notes' => 'array',
        'fee' => 'integer',
        'tax' => 'integer',
        'raw_response' => 'array',
    ];
}
