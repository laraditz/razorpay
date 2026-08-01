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
            'currency' => 'MYR',
            'status' => 'created',
            'short_url' => 'https://rzp.io/i/abc123',
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create([
            'amount' => 50000,
            'currency' => 'MYR',
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
            'currency' => 'MYR',
            'status' => 'created',
            'short_url' => 'https://rzp.io/i/abc123',
            'reference_id' => 'ref_1',
            'description' => 'Test payment',
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create(['amount' => 50000, 'currency' => 'MYR']);

        $paymentLink = PaymentLink::where('razorpay_id', 'plink_ExjpAUN3gVHrPJ')->first();

        $this->assertNotNull($paymentLink);
        $this->assertSame('order_ExjpAUN3gVHrPK', $paymentLink->order_id);
        $this->assertSame(PaymentLinkStatus::Created, $paymentLink->status);
        $this->assertSame(50000, $paymentLink->amount);
        $this->assertSame('MYR', $paymentLink->currency);
        $this->assertSame('https://rzp.io/i/abc123', $paymentLink->short_url);
        $this->assertSame('ref_1', $paymentLink->reference_id);
        $this->assertSame($responseBody, $paymentLink->raw_response);
    }

    public function test_create_with_no_expiry_configured_does_not_crash_on_epoch_zero(): void
    {
        // Razorpay returns expire_by: 0 (not null/omitted) when no expiry was
        // set on the link. 0 is a valid Unix timestamp (1970-01-01 00:00:00),
        // which MySQL's TIMESTAMP column type rejects as out of range -- it
        // must be treated as "no value", not converted to an epoch instant.
        $responseBody = [
            'id' => 'plink_TKMyGdApBilkoA',
            'amount' => 100,
            'currency' => 'MYR',
            'status' => 'created',
            'expire_by' => 0,
            'cancelled_at' => 0,
            'expired_at' => 0,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create(['amount' => 100, 'currency' => 'MYR']);

        $paymentLink = PaymentLink::where('razorpay_id', 'plink_TKMyGdApBilkoA')->first();

        $this->assertNotNull($paymentLink);
        $this->assertNull($paymentLink->expire_by);
    }

    public function test_create_with_expiry_configured_stores_the_expiry_datetime(): void
    {
        $expireByTimestamp = now()->addDay()->timestamp;

        $responseBody = [
            'id' => 'plink_ExjpAUN3gVHrPJ',
            'amount' => 50000,
            'currency' => 'MYR',
            'status' => 'created',
            'expire_by' => $expireByTimestamp,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create(['amount' => 50000, 'currency' => 'MYR']);

        $paymentLink = PaymentLink::where('razorpay_id', 'plink_ExjpAUN3gVHrPJ')->first();

        $this->assertNotNull($paymentLink->expire_by);
        $this->assertSame($expireByTimestamp, $paymentLink->expire_by->timestamp);
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
            'currency' => 'MYR',
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

    public function test_cancel_with_cancelled_at_epoch_zero_falls_back_to_now(): void
    {
        // Defensive: same bug pattern as expire_by -- if Razorpay ever sends
        // cancelled_at: 0 on a cancel response, it must not be converted to
        // the epoch instant (invalid for a MySQL TIMESTAMP column).
        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_ExjpAUN3gVHrPJ',
            'amount' => 50000,
            'currency' => 'MYR',
            'status' => PaymentLinkStatus::Created,
        ]);

        Http::fake(['*' => Http::response(['id' => 'plink_ExjpAUN3gVHrPJ', 'status' => 'cancelled', 'cancelled_at' => 0], 200)]);

        $this->makeService()->cancel('plink_ExjpAUN3gVHrPJ');

        $paymentLink->refresh();
        $this->assertNotNull($paymentLink->cancelled_at);
        $this->assertNotSame('1970-01-01 00:00:00', $paymentLink->cancelled_at->toDateTimeString());
    }

    public function test_cancel_failure_propagates_exception_without_touching_local_record(): void
    {
        $paymentLink = PaymentLink::create([
            'razorpay_id' => 'plink_ExjpAUN3gVHrPJ',
            'amount' => 50000,
            'currency' => 'MYR',
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

    public function test_notify_by_posts_to_notify_endpoint_with_medium(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 200)]);

        $result = $this->makeService()->notifyBy('plink_ExjpAUN3gVHrPJ', 'sms');

        $this->assertSame(['success' => true], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links/plink_ExjpAUN3gVHrPJ/notify_by/sms'
                && $request->method() === 'POST';
        });
    }

    public function test_notify_by_passes_medium_through_untouched(): void
    {
        Http::fake(['*' => Http::response(['error' => ['description' => 'invalid medium']], 400)]);

        $this->expectException(\Laraditz\Razorpay\Exceptions\ValidationException::class);

        $this->makeService()->notifyBy('plink_ExjpAUN3gVHrPJ', 'carrier_pigeon');
    }
}
