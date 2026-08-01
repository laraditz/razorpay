<?php

namespace Laraditz\Razorpay\Tests;

use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\RazorpayRefund;

class RefundWebhookEndToEndTest extends TestCase
{
    public function test_valid_refund_processed_webhook_updates_local_record_status(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $refund = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_e2e',
            'payment_id' => 'pay_e2e',
            'amount' => 10000,
            'currency' => 'MYR',
            'status' => RefundStatus::Pending,
        ]);

        $payload = [
            'event' => 'refund.processed',
            'payload' => [
                'refund' => [
                    'entity' => ['id' => 'rfnd_e2e', 'status' => 'processed', 'speed_processed' => 'normal'],
                ],
            ],
        ];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $response = $this->call('POST', config('razorpay.webhook_path'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(200);

        $refund->refresh();
        $this->assertSame(RefundStatus::Processed, $refund->status);
        $this->assertSame('normal', $refund->speed_processed);
        $this->assertNotNull($refund->processed_at);
    }
}
