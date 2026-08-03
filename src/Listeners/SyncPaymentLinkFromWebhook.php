<?php

namespace Laraditz\Razorpay\Listeners;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;

class SyncPaymentLinkFromWebhook
{
    public function handle(RazorpayWebhookReceived $event): void
    {
        $status = match ($event->eventType) {
            'payment_link.paid' => PaymentLinkStatus::Paid,
            'payment_link.cancelled' => PaymentLinkStatus::Cancelled,
            'payment_link.expired' => PaymentLinkStatus::Expired,
            default => null,
        };

        if ($status === null) {
            return;
        }

        $this->syncPaymentLink($event, $status);

        if ($status === PaymentLinkStatus::Paid) {
            $this->syncOrder($event);
        }
    }

    protected function syncPaymentLink(RazorpayWebhookReceived $event, PaymentLinkStatus $status): void
    {
        $razorpayId = data_get($event->payload, 'payload.payment_link.entity.id');

        if (!$razorpayId) {
            return;
        }

        $paymentLink = RazorpayPaymentLink::where('razorpay_id', $razorpayId)->first();

        if (!$paymentLink || $paymentLink->status === $status) {
            return;
        }

        $paymentLink->update(array_filter([
            'status' => $status,
            'paid_at' => $status === PaymentLinkStatus::Paid ? now() : null,
            'payment_id' => $status === PaymentLinkStatus::Paid
                ? data_get($event->payload, 'payload.payment.entity.id')
                : null,
            // Not present in Razorpay's response when the Payment Link is
            // first created (no payment has been attempted yet), but the
            // payment_link.paid payload always carries it. Not gated by
            // $status since it reflects the underlying order, not this
            // particular status transition.
            'order_id' => data_get($event->payload, 'payload.payment.entity.order_id'),
            'cancelled_at' => $status === PaymentLinkStatus::Cancelled ? now() : null,
            'expired_at' => $status === PaymentLinkStatus::Expired ? now() : null,
        ]));
    }

    /**
     * Runs independently of syncPaymentLink() -- the embedded order entity
     * is valid, useful data regardless of whether a local PaymentLink row
     * was found for this delivery.
     */
    protected function syncOrder(RazorpayWebhookReceived $event): void
    {
        $orderEntity = data_get($event->payload, 'payload.order.entity', []);
        $paymentId = data_get($event->payload, 'payload.payment.entity.id');

        RazorpayOrder::syncFromResponse($orderEntity, $paymentId);
    }
}
