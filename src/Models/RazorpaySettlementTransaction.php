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
}
