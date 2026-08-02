<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Services\PaymentService;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    protected function makeService(): PaymentService
    {
        return new PaymentService(new RazorpayClient());
    }

    public function test_fetch_gets_payment_and_syncs_local_record(): void
    {
        $responseBody = ['id' => 'pay_1', 'status' => 'captured', 'amount' => 50000, 'currency' => 'MYR', 'captured' => true];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetch('pay_1');

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payments/pay_1'
                && $request->method() === 'GET';
        });

        $payment = RazorpayPayment::where('razorpay_id', 'pay_1')->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Captured, $payment->status);
    }

    public function test_capture_posts_to_payment_capture_and_syncs_local_record(): void
    {
        $responseBody = ['id' => 'pay_1', 'status' => 'captured', 'amount' => 50000, 'currency' => 'MYR', 'captured' => true];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->capture('pay_1', ['amount' => 50000, 'currency' => 'MYR']);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payments/pay_1/capture'
                && $request->method() === 'POST'
                && $request['amount'] === 50000;
        });

        $payment = RazorpayPayment::where('razorpay_id', 'pay_1')->first();
        $this->assertNotNull($payment);
        $this->assertTrue($payment->captured);
    }

    public function test_update_patches_payment_and_syncs_local_record(): void
    {
        $responseBody = ['id' => 'pay_1', 'status' => 'captured', 'amount' => 50000, 'currency' => 'MYR', 'notes' => ['key' => 'value']];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->update('pay_1', ['notes' => ['key' => 'value']]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payments/pay_1'
                && $request->method() === 'PATCH';
        });

        $payment = RazorpayPayment::where('razorpay_id', 'pay_1')->first();
        $this->assertNotNull($payment);
        $this->assertSame(['key' => 'value'], $payment->notes);
    }

    public function test_all_forwards_query_params_and_syncs_every_item(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                ['id' => 'pay_1', 'status' => 'captured', 'amount' => 50000, 'currency' => 'MYR'],
                ['id' => 'pay_2', 'status' => 'failed', 'amount' => 20000, 'currency' => 'MYR'],
            ],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->all(['count' => 5]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.razorpay.com/v1/payments?')
                && $request->method() === 'GET'
                && $request['count'] == 5;
        });

        $this->assertSame(2, RazorpayPayment::count());
        $this->assertNotNull(RazorpayPayment::where('razorpay_id', 'pay_1')->first());
        $this->assertNotNull(RazorpayPayment::where('razorpay_id', 'pay_2')->first());
    }
}
