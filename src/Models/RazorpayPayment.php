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

    public static function syncFromResponse(array $data): ?self
    {
        if (empty($data['id'])) {
            return null;
        }

        return static::updateOrCreate(
            ['razorpay_id' => $data['id']],
            [
                'order_id' => $data['order_id'] ?? null,
                'status' => $data['status'] ?? null,
                'method' => $data['method'] ?? null,
                'amount' => $data['amount'] ?? null,
                'amount_refunded' => $data['amount_refunded'] ?? null,
                'currency' => $data['currency'] ?? null,
                'captured' => $data['captured'] ?? false,
                'description' => $data['description'] ?? null,
                'email' => $data['email'] ?? null,
                'contact' => $data['contact'] ?? null,
                'notes' => $data['notes'] ?? null,
                'fee' => $data['fee'] ?? null,
                'tax' => $data['tax'] ?? null,
                'error_code' => $data['error_code'] ?? null,
                'error_description' => $data['error_description'] ?? null,
                'raw_response' => $data,
            ]
        );
    }
}
