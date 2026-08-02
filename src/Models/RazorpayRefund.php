<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Razorpay\Enums\RefundStatus;

class RazorpayRefund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'razorpay_id',
        'payment_id',
        'status',
        'amount',
        'currency',
        'notes',
        'receipt',
        'speed_requested',
        'speed_processed',
        'raw_response',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'status' => RefundStatus::class,
        'amount' => 'integer',
        'notes' => 'array',
        'raw_response' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RazorpayPayment::class, 'payment_id', 'razorpay_id');
    }
}
