<?php

namespace Laraditz\Razorpay\Tests\Events;

use Laraditz\Razorpay\Events\OrderPaid;
use Laraditz\Razorpay\Events\RefundCreated;
use Laraditz\Razorpay\Events\RefundFailed;
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Models\Order;
use Laraditz\Razorpay\Models\Refund;
use Laraditz\Razorpay\Tests\TestCase;

class OrderRefundEventsTest extends TestCase
{
    public function test_order_paid_stores_model_and_payload(): void
    {
        $order = new Order(['razorpay_id' => 'order_1']);
        $event = new OrderPaid($order, ['id' => 'order_1']);

        $this->assertSame($order, $event->order);
        $this->assertSame(['id' => 'order_1'], $event->payload);
    }

    public function test_order_paid_supports_null_model(): void
    {
        $event = new OrderPaid(null, ['id' => 'order_1']);

        $this->assertNull($event->order);
    }

    public function test_refund_created_stores_model_and_payload(): void
    {
        $refund = new Refund(['razorpay_id' => 'rfnd_1']);
        $event = new RefundCreated($refund, ['id' => 'rfnd_1']);

        $this->assertSame($refund, $event->refund);
        $this->assertSame(['id' => 'rfnd_1'], $event->payload);
    }

    public function test_refund_processed_stores_nullable_model_and_payload(): void
    {
        $event = new RefundProcessed(null, ['id' => 'rfnd_1']);

        $this->assertNull($event->refund);
        $this->assertSame(['id' => 'rfnd_1'], $event->payload);
    }

    public function test_refund_failed_stores_nullable_model_and_payload(): void
    {
        $event = new RefundFailed(null, ['id' => 'rfnd_1']);

        $this->assertNull($event->refund);
        $this->assertSame(['id' => 'rfnd_1'], $event->payload);
    }
}
