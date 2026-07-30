<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\Refund;
use Laraditz\Razorpay\Services\RefundService;
use Laraditz\Razorpay\Tests\TestCase;

class RefundServiceTest extends TestCase
{
    protected function makeService(): RefundService
    {
        return new RefundService(new RazorpayClient());
    }

    public function test_create_posts_to_payment_refunds_and_returns_array(): void
    {
        $responseBody = [
            'id' => 'rfnd_EL845GtTZl41Xn',
            'payment_id' => 'pay_1',
            'amount' => 10000,
            'currency' => 'MYR',
            'status' => 'pending',
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create('pay_1', ['amount' => 10000]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payments/pay_1/refunds'
                && $request->method() === 'POST'
                && $request['amount'] === 10000;
        });
    }

    public function test_create_with_omitted_amount_still_sends_valid_request(): void
    {
        $responseBody = ['id' => 'rfnd_EL845GtTZl41Xn', 'payment_id' => 'pay_1', 'amount' => 50000, 'currency' => 'MYR', 'status' => 'pending'];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create('pay_1');

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payments/pay_1/refunds'
                && $request->method() === 'POST'
                && !array_key_exists('amount', $request->data());
        });
    }

    public function test_create_persists_a_local_refund_record(): void
    {
        $responseBody = [
            'id' => 'rfnd_EL845GtTZl41Xn',
            'payment_id' => 'pay_1',
            'amount' => 10000,
            'currency' => 'MYR',
            'status' => 'pending',
            'receipt' => 'receipt_1',
            'speed_requested' => 'normal',
            'speed_processed' => null,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create('pay_1', ['amount' => 10000]);

        $refund = Refund::where('razorpay_id', 'rfnd_EL845GtTZl41Xn')->first();

        $this->assertNotNull($refund);
        $this->assertSame('pay_1', $refund->payment_id);
        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertSame(10000, $refund->amount);
        $this->assertSame('MYR', $refund->currency);
        $this->assertSame('receipt_1', $refund->receipt);
        $this->assertSame('normal', $refund->speed_requested);
        $this->assertSame($responseBody, $refund->raw_response);
    }
}
