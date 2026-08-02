<?php

namespace Laraditz\Razorpay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laraditz\Razorpay\Models\RazorpaySettlement;

class SettlementProcessed
{
    use Dispatchable, SerializesModels;

    public ?RazorpaySettlement $settlement;
    public array $payload;

    public function __construct(?RazorpaySettlement $settlement, array $payload)
    {
        $this->settlement = $settlement;
        $this->payload = $payload;
    }
}
