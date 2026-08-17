<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Razorpay\Enums\RazorpaySettlementTransactionType;

class RazorpaySettlementTransaction extends Model
{
    protected $fillable = [
        'entity_id',
        'type',
        'settlement_id',
        'debit',
        'credit',
        'amount',
        'currency',
        'fee',
        'tax',
        'settled',
        'on_hold',
        'settled_at',
        'transaction_created_at',
        'payment_id',
        'order_id',
        'order_receipt',
        'settlement_utr',
        'dispute_id',
        'method',
        'card_network',
        'card_issuer',
        'card_type',
        'description',
        'notes',
        'posted_at',
        'credit_type',
    ];

    protected $casts = [
        'type' => RazorpaySettlementTransactionType::class,
        'debit' => 'integer',
        'credit' => 'integer',
        'amount' => 'integer',
        'fee' => 'integer',
        'tax' => 'integer',
        'settled' => 'boolean',
        'on_hold' => 'boolean',
        'settled_at' => 'datetime',
        'transaction_created_at' => 'datetime',
        'notes' => 'array',
        'posted_at' => 'datetime',
    ];

    public static function syncFromResponse(array $data): ?self
    {
        if (empty($data['entity_id']) || empty($data['type'])) {
            return null;
        }

        return static::updateOrCreate(
            ['entity_id' => $data['entity_id'], 'type' => $data['type']],
            [
                'settlement_id' => $data['settlement_id'] ?? null,
                'debit' => $data['debit'] ?? null,
                'credit' => $data['credit'] ?? null,
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'fee' => $data['fee'] ?? null,
                'tax' => $data['tax'] ?? null,
                'settled' => $data['settled'] ?? false,
                'on_hold' => $data['on_hold'] ?? false,
                'settled_at' => self::nullableTimestamp($data['settled_at'] ?? null),
                'transaction_created_at' => self::nullableTimestamp($data['created_at'] ?? null),
                'payment_id' => $data['payment_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'order_receipt' => $data['order_receipt'] ?? null,
                'settlement_utr' => $data['settlement_utr'] ?? null,
                'dispute_id' => $data['dispute_id'] ?? null,
                'method' => $data['method'] ?? null,
                'card_network' => $data['card_network'] ?? null,
                'card_issuer' => $data['card_issuer'] ?? null,
                'card_type' => $data['card_type'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'posted_at' => self::nullableTimestamp($data['posted_at'] ?? null),
                'credit_type' => $data['credit_type'] ?? null,
            ]
        );
    }

    /**
     * Razorpay returns 0 (not null) for unset Unix-timestamp fields — 0 is a
     * valid timestamp (1970-01-01 00:00:00), which is out of range for a
     * MySQL TIMESTAMP column, so it must be treated as "no value". Same fix
     * as RazorpaySettlement::nullableTimestamp() / PaymentLinkService::nullableTimestamp().
     */
    protected static function nullableTimestamp(?int $timestamp): ?\Illuminate\Support\Carbon
    {
        return $timestamp ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp) : null;
    }
}
