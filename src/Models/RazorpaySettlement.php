<?php

namespace Laraditz\Razorpay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function transactions(): HasMany
    {
        return $this->hasMany(RazorpaySettlementTransaction::class, 'settlement_id', 'razorpay_id');
    }

    public static function syncFromResponse(array $data): ?self
    {
        if (empty($data['id'])) {
            return null;
        }

        $isProcessed = ($data['status'] ?? null) === 'processed';

        return static::updateOrCreate(
            ['razorpay_id' => $data['id']],
            [
                'amount' => $data['amount'] ?? null,
                'fees' => $data['fees'] ?? null,
                'tax' => $data['tax'] ?? null,
                'utr' => $data['utr'] ?? null,
                'status' => $data['status'] ?? null,
                'settled_at' => $isProcessed ? self::nullableTimestamp($data['created_at'] ?? null) : null,
                'raw_response' => $data,
            ]
        );
    }

    /**
     * Razorpay returns 0 (not null) for unset Unix-timestamp fields — 0 is a
     * valid timestamp (1970-01-01 00:00:00), which is out of range for a
     * MySQL TIMESTAMP column, so it must be treated as "no value". Same fix
     * as PaymentLinkService::nullableTimestamp().
     */
    protected static function nullableTimestamp(?int $timestamp): ?\Illuminate\Support\Carbon
    {
        return $timestamp ? \Illuminate\Support\Carbon::createFromTimestamp($timestamp) : null;
    }
}
