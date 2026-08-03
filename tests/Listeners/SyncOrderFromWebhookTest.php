<?php

namespace Laraditz\Razorpay\Tests\Listeners;

use Illuminate\Support\Carbon;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncOrderFromWebhook;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Tests\TestCase;

class SyncOrderFromWebhookTest extends TestCase
{
    protected function makeOrder(): RazorpayOrder
    {
        return RazorpayOrder::create([
            'razorpay_id' => 'order_1',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'status' => OrderStatus::Created,
        ]);
    }

    protected function makeEvent(): RazorpayWebhookReceived
    {
        return new RazorpayWebhookReceived('order.paid', [
            'event' => 'order.paid',
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_1',
                        'status' => 'paid',
                        'amount' => 50000,
                        'amount_paid' => 50000,
                        'amount_due' => 0,
                        'currency' => 'MYR',
                    ],
                ],
                'payment' => [
                    'entity' => ['id' => 'pay_abc123'],
                ],
            ],
        ]);
    }

    public function test_order_paid_updates_status_and_amounts(): void
    {
        $order = $this->makeOrder();

        (new SyncOrderFromWebhook())->handle($this->makeEvent());

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(50000, $order->amount_paid);
        $this->assertSame(0, $order->amount_due);
        $this->assertSame('pay_abc123', $order->payment_id);
    }

    public function test_order_paid_leaves_payment_id_null_when_payment_entity_missing(): void
    {
        $order = $this->makeOrder();

        $event = new RazorpayWebhookReceived('order.paid', [
            'event' => 'order.paid',
            'payload' => [
                'order' => ['entity' => ['id' => 'order_1', 'status' => 'paid', 'amount' => 50000, 'amount_paid' => 50000, 'amount_due' => 0, 'currency' => 'MYR']],
            ],
        ]);

        (new SyncOrderFromWebhook())->handle($event);

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNull($order->payment_id);
    }

    public function test_redelivering_the_same_event_is_idempotent(): void
    {
        $order = $this->makeOrder();
        $listener = new SyncOrderFromWebhook();

        Carbon::setTestNow('2026-01-01 00:00:00');
        $listener->handle($this->makeEvent());
        $order->refresh();
        $firstAmountPaid = $order->amount_paid;
        $firstPaymentId = $order->payment_id;
        $firstPaidAt = $order->paid_at;

        Carbon::setTestNow('2026-01-01 00:05:00');
        $listener->handle($this->makeEvent());
        $order->refresh();

        Carbon::setTestNow();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame($firstAmountPaid, $order->amount_paid);
        $this->assertSame($firstPaymentId, $order->payment_id);
        $this->assertSame($firstPaidAt->toIso8601String(), $order->paid_at->toIso8601String());
    }

    public function test_creates_a_local_record_when_none_exists(): void
    {
        $this->assertSame(0, RazorpayOrder::count());

        (new SyncOrderFromWebhook())->handle($this->makeEvent());

        $order = RazorpayOrder::where('razorpay_id', 'order_1')->first();
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('pay_abc123', $order->payment_id);
    }

    public function test_missing_order_entity_id_does_not_throw(): void
    {
        $event = new RazorpayWebhookReceived('order.paid', [
            'event' => 'order.paid',
            'payload' => ['order' => ['entity' => ['status' => 'paid']]],
        ]);

        (new SyncOrderFromWebhook())->handle($event);

        $this->assertSame(0, RazorpayOrder::count());
    }
}
