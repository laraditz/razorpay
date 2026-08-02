<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentSyncFromResponseTest extends TestCase
{
    protected function makeResponse(array $overrides = []): array
    {
        return array_merge([
            'id' => 'pay_1',
            'order_id' => 'order_1',
            'status' => 'captured',
            'method' => 'card',
            'amount' => 50000,
            'currency' => 'MYR',
            'captured' => true,
        ], $overrides);
    }

    public function test_valid_response_creates_a_row(): void
    {
        $payment = RazorpayPayment::syncFromResponse($this->makeResponse());

        $this->assertNotNull($payment);
        $this->assertSame('pay_1', $payment->razorpay_id);
        $this->assertSame(PaymentStatus::Captured, $payment->status);
        $this->assertSame(1, RazorpayPayment::count());
    }

    public function test_syncing_same_id_updates_instead_of_duplicating(): void
    {
        RazorpayPayment::syncFromResponse($this->makeResponse(['status' => 'authorized']));
        RazorpayPayment::syncFromResponse($this->makeResponse(['status' => 'captured']));

        $this->assertSame(1, RazorpayPayment::count());
        $this->assertSame(PaymentStatus::Captured, RazorpayPayment::first()->status);
    }

    public function test_missing_id_returns_null_and_writes_nothing(): void
    {
        $result = RazorpayPayment::syncFromResponse(['status' => 'captured']);

        $this->assertNull($result);
        $this->assertSame(0, RazorpayPayment::count());
    }
}
