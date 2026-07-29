<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Exceptions\RazorpayException;
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

    public function test_update_patches_payment_link_and_returns_array(): void
    {
        $responseBody = ['id' => 'plink_ExjpAUN3gVHrPJ', 'reference_id' => 'ref_2'];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->update('plink_ExjpAUN3gVHrPJ', ['reference_id' => 'ref_2']);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_ExjpAUN3gVHrPJ'
                && $request->method() === 'PATCH'
                && $request['reference_id'] === 'ref_2';
        });
    }

    public function test_cancel_posts_cancel_and_updates_local_record_status(): void
    {
        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_ExjpAUN3gVHrPJ',
            'amount' => 50000,
            'currency' => 'INR',
            'status' => PaymentLinkStatus::Created,
        ]);

        $responseBody = ['id' => 'plink_ExjpAUN3gVHrPJ', 'status' => 'cancelled', 'cancelled_at' => now()->timestamp];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->cancel('plink_ExjpAUN3gVHrPJ');

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_ExjpAUN3gVHrPJ/cancel'
                && $request->method() === 'POST';
        });

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Cancelled, $paymentLink->status);
        $this->assertNotNull($paymentLink->cancelled_at);
    }

    public function test_cancel_failure_propagates_exception_without_touching_local_record(): void
    {
        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_ExjpAUN3gVHrPJ',
            'amount' => 50000,
            'currency' => 'INR',
            'status' => PaymentLinkStatus::Paid,
        ]);

        Http::fake(['*' => Http::response(['error' => ['description' => 'already paid']], 400)]);

        try {
            $this->makeService()->cancel('plink_ExjpAUN3gVHrPJ');
            $this->fail('Expected an exception to be thrown.');
        } catch (RazorpayException $e) {
            // expected
        }

        $paymentLink->refresh();
        $this->assertSame(PaymentLinkStatus::Paid, $paymentLink->status);
    }

    public function test_all_forwards_query_params_and_returns_list_envelope(): void
    {
        $responseBody = ['entity' => 'collection', 'count' => 1, 'items' => [['id' => 'plink_1']]];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->all(['count' => 5, 'skip' => 0, 'reference_id' => 'ref_1']);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.razorpay.com/v1/payment_links?')
                && $request->method() === 'GET'
                && $request['count'] == 5
                && $request['reference_id'] === 'ref_1';
        });
    }
}
