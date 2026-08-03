<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;

class RazorpayPaymentLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'razorpay_id',
        'order_id',
        'payment_id',
        'subject_id',
        'subject_type',
        'status',
        'amount',
        'amount_paid',
        'currency',
        'reference_id',
        'description',
        'customer_name',
        'customer_email',
        'customer_contact',
        'notify_sms',
        'notify_email',
        'reminder_enable',
        'accept_partial',
        'first_min_partial_amount',
        'notes',
        'callback_url',
        'callback_method',
        'short_url',
        'raw_response',
        'expire_by',
        'paid_at',
        'cancelled_at',
        'expired_at',
    ];

    protected $casts = [
        'status' => PaymentLinkStatus::class,
        'amount' => 'integer',
        'amount_paid' => 'integer',
        'first_min_partial_amount' => 'integer',
        'notify_sms' => 'boolean',
        'notify_email' => 'boolean',
        'reminder_enable' => 'boolean',
        'accept_partial' => 'boolean',
        'notes' => 'array',
        'raw_response' => 'array',
        'expire_by' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RazorpayPayment::class, 'payment_id', 'razorpay_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
