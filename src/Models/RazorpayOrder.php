<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laraditz\Razorpay\Enums\OrderStatus;

class RazorpayOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'razorpay_id',
        'payment_id',
        'subject_id',
        'subject_type',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(RazorpayPayment::class, 'payment_id', 'razorpay_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function syncFromResponse(array $data, ?string $paymentId = null): ?self
    {
        if (empty($data['id'])) {
            return null;
        }

        $order = static::updateOrCreate(
            ['razorpay_id' => $data['id']],
            [
                'status' => $data['status'] ?? null,
                'amount' => $data['amount'] ?? null,
                'amount_paid' => $data['amount_paid'] ?? null,
                'amount_due' => $data['amount_due'] ?? null,
                'currency' => $data['currency'] ?? null,
                'receipt' => $data['receipt'] ?? null,
                'attempts' => $data['attempts'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'raw_response' => $data,
            ]
        );

        $order->update(array_filter([
            'payment_id' => $paymentId,
            'paid_at' => ($data['status'] ?? null) === 'paid' ? ($order->paid_at ?? now()) : null,
        ]));

        return $order;
    }
}
