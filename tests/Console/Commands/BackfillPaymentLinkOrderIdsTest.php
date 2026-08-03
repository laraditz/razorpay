<?php

namespace Laraditz\Razorpay\Tests\Console\Commands;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Enums\PaymentLinkStatus;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Tests\TestCase;

class BackfillPaymentLinkOrderIdsTest extends TestCase
{
    protected function makePaymentLink(string $razorpayId, ?string $orderId = null): RazorpayPaymentLink
    {
        return RazorpayPaymentLink::create([
            'razorpay_id' => $razorpayId,
            'order_id' => $orderId,
            'amount' => 1000,
            'currency' => 'MYR',
            'status' => PaymentLinkStatus::Paid,
        ]);
    }

    public function test_backfills_order_id_when_missing_locally_and_present_in_response(): void
    {
        $paymentLink = $this->makePaymentLink('plink_1');

        Http::fake([
            'https://api.razorpay.com/v1/payment_links/plink_1' => Http::response(['id' => 'plink_1', 'order_id' => 'order_abc'], 200),
        ]);

        $this->artisan('razorpay:backfill-payment-link-order-ids')->assertSuccessful();

        $paymentLink->refresh();
        $this->assertSame('order_abc', $paymentLink->order_id);
    }

    public function test_skips_when_response_still_has_no_order_id(): void
    {
        $paymentLink = $this->makePaymentLink('plink_2');

        Http::fake([
            'https://api.razorpay.com/v1/payment_links/plink_2' => Http::response(['id' => 'plink_2'], 200),
        ]);

        $this->artisan('razorpay:backfill-payment-link-order-ids')->assertSuccessful();

        $paymentLink->refresh();
        $this->assertNull($paymentLink->order_id);
    }

    public function test_does_not_touch_payment_links_that_already_have_order_id(): void
    {
        $paymentLink = $this->makePaymentLink('plink_3', 'order_existing');

        Http::fake();

        $this->artisan('razorpay:backfill-payment-link-order-ids')->assertSuccessful();

        Http::assertNothingSent();

        $paymentLink->refresh();
        $this->assertSame('order_existing', $paymentLink->order_id);
    }

    public function test_continues_processing_after_one_record_fails(): void
    {
        $failing = $this->makePaymentLink('plink_fail');
        $succeeding = $this->makePaymentLink('plink_ok');

        Http::fake([
            'https://api.razorpay.com/v1/payment_links/plink_fail' => Http::response(['error' => ['description' => 'not found']], 404),
            'https://api.razorpay.com/v1/payment_links/plink_ok' => Http::response(['id' => 'plink_ok', 'order_id' => 'order_ok'], 200),
        ]);

        $this->artisan('razorpay:backfill-payment-link-order-ids')->assertSuccessful();

        $succeeding->refresh();
        $this->assertSame('order_ok', $succeeding->order_id);

        $failing->refresh();
        $this->assertNull($failing->order_id);
    }
}
