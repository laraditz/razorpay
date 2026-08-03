<?php

namespace Laraditz\Razorpay\Tests\Models;

use Illuminate\Support\Carbon;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Tests\TestCase;

class OrderSyncFromResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function makeResponse(array $overrides = []): array
    {
        return array_merge([
            'id' => 'order_1',
            'status' => 'created',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'receipt' => 'receipt_1',
            'attempts' => 0,
        ], $overrides);
    }

    public function test_creates_a_row_when_missing(): void
    {
        $order = RazorpayOrder::syncFromResponse($this->makeResponse());

        $this->assertNotNull($order);
        $this->assertSame('order_1', $order->razorpay_id);
        $this->assertSame(OrderStatus::Created, $order->status);
        $this->assertSame(1, RazorpayOrder::count());
    }

    public function test_updates_in_place_when_already_present(): void
    {
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'created']));
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'paid', 'amount_paid' => 50000, 'amount_due' => 0]));

        $this->assertSame(1, RazorpayOrder::count());
        $order = RazorpayOrder::first();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(50000, $order->amount_paid);
    }

    public function test_missing_id_returns_null_and_writes_nothing(): void
    {
        $result = RazorpayOrder::syncFromResponse(['status' => 'created']);

        $this->assertNull($result);
        $this->assertSame(0, RazorpayOrder::count());
    }

    public function test_payment_id_only_written_when_provided(): void
    {
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'paid']), 'pay_1');

        // Re-sync without a payment id — must not erase the one already stored
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'paid']));

        $order = RazorpayOrder::first();
        $this->assertSame('pay_1', $order->payment_id);
    }

    public function test_paid_at_is_set_once_and_does_not_drift(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'paid']));
        $order = RazorpayOrder::first();
        $firstPaidAt = $order->paid_at;

        Carbon::setTestNow('2026-01-01 00:05:00');
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'paid']));
        $order->refresh();

        $this->assertSame($firstPaidAt->toIso8601String(), $order->paid_at->toIso8601String());
    }

    public function test_non_paid_status_leaves_paid_at_null(): void
    {
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'created']));

        $order = RazorpayOrder::first();
        $this->assertNull($order->paid_at);
    }

    public function test_subject_id_and_type_are_untouched_by_this_method(): void
    {
        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'created']));

        $order = RazorpayOrder::first();
        $order->update(['subject_id' => 42, 'subject_type' => 'App\\Models\\Invoice']);

        RazorpayOrder::syncFromResponse($this->makeResponse(['status' => 'paid']));

        $order->refresh();
        $this->assertSame(42, $order->subject_id);
        $this->assertSame('App\\Models\\Invoice', $order->subject_type);
    }
}
