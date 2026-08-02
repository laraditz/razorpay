<?php

namespace Laraditz\Razorpay\Listeners;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Events\RazorpayWebhookReceived;
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
            'cancelled_at' => $status === PaymentLinkStatus::Cancelled ? now() : null,
            'expired_at' => $status === PaymentLinkStatus::Expired ? now() : null,
        ]));
    }
}
