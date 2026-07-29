<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\PaymentLink;
use Laraditz\Razorpay\Services\PaymentLinkService;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentLinkServiceTest extends TestCase
{
    protected function makeService(): PaymentLinkService
    {
        return new PaymentLinkService(new RazorpayClient());
    }

    public function test_create_posts_to_payment_links_and_returns_array(): void
    {
        $responseBody = [
            'id' => 'plink_ExjpAUN3gVHrPJ',
            'order_id' => 'order_ExjpAUN3gVHrPK',
            'amount' => 50000,
            'currency' => 'INR',
            'status' => 'created',
            'short_url' => 'https://rzp.io/i/abc123',
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create([
            'amount' => 50000,
            'currency' => 'INR',
            'description' => 'Test payment',
        ]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links'
                && $request->method() === 'POST'
                && $request['amount'] === 50000
                && $request['description'] === 'Test payment';
        });
    }

    public function test_create_persists_a_local_payment_link_record(): void
    {
        $responseBody = [
            'id' => 'plink_ExjpAUN3gVHrPJ',
            'order_id' => 'order_ExjpAUN3gVHrPK',
            'amount' => 50000,
            'amount_paid' => 0,
            'currency' => 'INR',
            'status' => 'created',
            'short_url' => 'https://rzp.io/i/abc123',
            'reference_id' => 'ref_1',
            'description' => 'Test payment',
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create(['amount' => 50000, 'currency' => 'INR']);

        $paymentLink = PaymentLink::where('razorpay_id', 'plink_ExjpAUN3gVHrPJ')->first();

        $this->assertNotNull($paymentLink);
        $this->assertSame('order_ExjpAUN3gVHrPK', $paymentLink->order_id);
        $this->assertSame(PaymentLinkStatus::Created, $paymentLink->status);
        $this->assertSame(50000, $paymentLink->amount);
        $this->assertSame('INR', $paymentLink->currency);
        $this->assertSame('https://rzp.io/i/abc123', $paymentLink->short_url);
        $this->assertSame('ref_1', $paymentLink->reference_id);
        $this->assertSame($responseBody, $paymentLink->raw_response);
    }

    public function test_fetch_gets_payment_link_and_does_not_touch_local_record(): void
    {
        $responseBody = ['id' => 'plink_ExjpAUN3gVHrPJ', 'status' => 'paid'];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetch('plink_ExjpAUN3gVHrPJ');

        $this->assertSame($responseBody, $result);
        $this->assertSame(0, PaymentLink::count());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_ExjpAUN3gVHrPJ'
                && $request->method() === 'GET';
        });
    }
}
