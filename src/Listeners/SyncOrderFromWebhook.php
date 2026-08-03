<?php

namespace Laraditz\Razorpay\Listeners;

use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\RazorpayOrder;

class SyncOrderFromWebhook
{
    public function handle(RazorpayWebhookReceived $event): void
    {
        if ($event->eventType !== 'order.paid') {
            return;
        }

        $orderEntity = data_get($event->payload, 'payload.order.entity', []);
        $paymentId = data_get($event->payload, 'payload.payment.entity.id');

        RazorpayOrder::syncFromResponse($orderEntity, $paymentId);
    }
}
