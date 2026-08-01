<?php

namespace Laraditz\Razorpay\Support;

use Laraditz\Razorpay\Events\OrderPaid;
use Laraditz\Razorpay\Events\PaymentCaptured;
use Laraditz\Razorpay\Events\PaymentFailed;
use Laraditz\Razorpay\Events\PaymentLinkPaid;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Events\RefundCreated;
use Laraditz\Razorpay\Events\RefundFailed;
use Laraditz\Razorpay\Events\RefundProcessed;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Models\RazorpayRefund;

class WebhookHandler
{
    /**
     * Handle a verified incoming webhook payload.
     */
    public function handle(array $payload): void
    {
        $eventType = $this->getEventType($payload);

        event(new RazorpayWebhookReceived($eventType, $payload));

        match ($eventType) {
            'payment_link.paid' => $this->handlePaymentLinkPaid($payload),
            'payment.captured' => $this->handlePaymentCaptured($payload),
            'payment.failed' => $this->handlePaymentFailed($payload),
            'order.paid' => $this->handleOrderPaid($payload),
            'refund.created' => $this->handleRefundCreated($payload),
            'refund.processed' => $this->handleRefundProcessed($payload),
            'refund.failed' => $this->handleRefundFailed($payload),
            default => null,
        };
    }

    protected function handleOrderPaid(array $payload): void
    {
        $razorpayId = data_get($payload, 'payload.order.entity.id');
        $order = $razorpayId ? RazorpayOrder::where('razorpay_id', $razorpayId)->first() : null;

        event(new OrderPaid($order, $payload));
    }

    protected function handleRefundCreated(array $payload): void
    {
        event(new RefundCreated($this->findRefund($payload), $payload));
    }

    protected function handleRefundProcessed(array $payload): void
    {
        event(new RefundProcessed($this->findRefund($payload), $payload));
    }

    protected function handleRefundFailed(array $payload): void
    {
        event(new RefundFailed($this->findRefund($payload), $payload));
    }

    protected function findRefund(array $payload): ?RazorpayRefund
    {
        $razorpayId = data_get($payload, 'payload.refund.entity.id');

        return $razorpayId ? RazorpayRefund::where('razorpay_id', $razorpayId)->first() : null;
    }

    protected function handlePaymentLinkPaid(array $payload): void
    {
        $razorpayId = data_get($payload, 'payload.payment_link.entity.id');
        $paymentLink = $razorpayId ? RazorpayPaymentLink::where('razorpay_id', $razorpayId)->first() : null;

        event(new PaymentLinkPaid($paymentLink, $payload));
    }

    protected function handlePaymentCaptured(array $payload): void
    {
        event(new PaymentCaptured($this->findByOrderId($payload), $payload));
    }

    protected function handlePaymentFailed(array $payload): void
    {
        event(new PaymentFailed($this->findByOrderId($payload), $payload));
    }

    protected function findByOrderId(array $payload): ?RazorpayPaymentLink
    {
        $orderId = data_get($payload, 'payload.payment.entity.order_id');

        return $orderId ? RazorpayPaymentLink::where('order_id', $orderId)->first() : null;
    }

    protected function getEventType(array $payload): string
    {
        return $payload['event'] ?? 'unknown';
    }
}
