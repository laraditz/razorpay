<?php

namespace Laraditz\Razorpay\Console\Commands;

use Illuminate\Console\Command;
use Laraditz\Razorpay\Models\RazorpayPaymentLink;
use Laraditz\Razorpay\Services\PaymentLinkService;

class BackfillPaymentLinkOrderIds extends Command
{
    protected $signature = 'razorpay:backfill-payment-link-order-ids';

    protected $description = 'Backfill order_id on razorpay_payment_links rows missing it by re-fetching each from Razorpay';

    public function handle(PaymentLinkService $service): int
    {
        $paymentLinks = RazorpayPaymentLink::whereNull('order_id')->get();

        $this->info("Found {$paymentLinks->count()} payment link(s) missing order_id.");

        $updated = 0;
        $skipped = 0;

        foreach ($paymentLinks as $paymentLink) {
            try {
                $response = $service->fetch($paymentLink->razorpay_id);
            } catch (\Throwable $e) {
                $this->warn("Skipped {$paymentLink->razorpay_id}: {$e->getMessage()}");
                $skipped++;
                continue;
            }

            $orderId = $response['order_id'] ?? null;

            if ($orderId === null) {
                $skipped++;
                continue;
            }

            $paymentLink->update(['order_id' => $orderId]);
            $updated++;
        }

        $this->info("Backfilled {$updated} payment link(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
