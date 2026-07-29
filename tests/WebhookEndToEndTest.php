<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\PaymentLink;

class WebhookEndToEndTest extends TestCase
{
    public function test_valid_payment_link_paid_webhook_updates_local_record_status(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_e2e',
            'amount' => 1000,
            'currency' => 'INR',
            'status' => PaymentLinkStatus::Created,
        ]);

        $payload = [
            'event' => 'payment_link.paid',
            'payload' => ['payment_link' => ['entity' => ['id' => 'plink_e2e']]],
        ];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $response = $this->call('POST', config('razorpay.webhook_path'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(200);

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
        $this->assertNotNull($paymentLink->paid_at);
    }
}
