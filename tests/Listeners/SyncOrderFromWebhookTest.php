<?php

namespace Laraditz\Razorpay\Tests\Listeners;

use Illuminate\Support\Carbon;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Listeners\SyncOrderFromWebhook;
use Laraditz\Razorpay\Models\Order;
use Laraditz\Razorpay\Tests\TestCase;

class SyncOrderFromWebhookTest extends TestCase
{
    protected function makeOrder(): Order
    {
        return Order::create([
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
                        'amount_paid' => 50000,
                        'amount_due' => 0,
                    ],
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
    }

    public function test_redelivering_the_same_event_is_idempotent(): void
    {
        $order = $this->makeOrder();
        $listener = new SyncOrderFromWebhook();

        Carbon::setTestNow('2026-01-01 00:00:00');
        $listener->handle($this->makeEvent());
        $order->refresh();
        $firstAmountPaid = $order->amount_paid;

        Carbon::setTestNow('2026-01-01 00:05:00');
        $listener->handle($this->makeEvent());
        $order->refresh();

        Carbon::setTestNow();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame($firstAmountPaid, $order->amount_paid);
    }

    public function test_no_matching_local_record_does_not_throw(): void
    {
        $event = new RazorpayWebhookReceived('order.paid', [
            'event' => 'order.paid',
            'payload' => ['order' => ['entity' => ['id' => 'order_does_not_exist']]],
        ]);

        (new SyncOrderFromWebhook())->handle($event);

        $this->assertTrue(true);
    }
}
