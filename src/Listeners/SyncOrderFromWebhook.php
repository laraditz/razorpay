<?php

namespace Laraditz\Razorpay\Listeners;

use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\RazorpayOrder;

class SyncOrderFromWebhook
{
    public function handle(RazorpayWebhookReceived $event): void
    {
        if ($event->eventType !== 'order.paid') {
            return;
        }

        $razorpayId = data_get($event->payload, 'payload.order.entity.id');

        if (!$razorpayId) {
            return;
        }

        $order = RazorpayOrder::where('razorpay_id', $razorpayId)->first();

        if (!$order || $order->status === OrderStatus::Paid) {
            return;
        }

        $order->update([
            'status' => OrderStatus::Paid,
            'amount_paid' => data_get($event->payload, 'payload.order.entity.amount_paid'),
            'amount_due' => data_get($event->payload, 'payload.order.entity.amount_due'),
            'payment_id' => data_get($event->payload, 'payload.payment.entity.id'),
            'paid_at' => now(),
        ]);
    }
}
