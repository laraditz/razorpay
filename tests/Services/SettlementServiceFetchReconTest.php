<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\RazorpaySettlementTransaction;
use Laraditz\Razorpay\Services\SettlementService;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementServiceFetchReconTest extends TestCase
{
    protected function makeService(): SettlementService
    {
        return new SettlementService(new RazorpayClient());
    }

    public function test_fetch_recon_sends_year_and_month_and_syncs_every_item(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                ['entity_id' => 'pay_1', 'type' => 'payment', 'settlement_id' => 'setl_1'],
                ['entity_id' => 'rfnd_1', 'type' => 'refund', 'settlement_id' => 'setl_1'],
            ],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetchRecon(2026, 8);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.razorpay.com/v1/settlements/recon/combined?')
                && $request->method() === 'GET'
                && $request['year'] == 2026
                && $request['month'] == 8
                && ! $request->hasHeader('day')
                && ! array_key_exists('day', $request->data())
                && ! array_key_exists('count', $request->data())
                && ! array_key_exists('skip', $request->data());
        });

        $this->assertSame(2, RazorpaySettlementTransaction::count());
        $this->assertNotNull(RazorpaySettlementTransaction::where('entity_id', 'pay_1')->first());
        $this->assertNotNull(RazorpaySettlementTransaction::where('entity_id', 'rfnd_1')->first());
    }

    public function test_fetch_recon_forwards_day_count_and_skip_when_provided(): void
    {
        Http::fake(['*' => Http::response(['entity' => 'collection', 'count' => 0, 'items' => []], 200)]);

        $this->makeService()->fetchRecon(2026, 8, 11, 50, 10);

        Http::assertSent(function ($request) {
            return $request['year'] == 2026
                && $request['month'] == 8
                && $request['day'] == 11
                && $request['count'] == 50
                && $request['skip'] == 10;
        });
    }

    public function test_fetch_recon_with_no_items_is_a_no_op(): void
    {
        Http::fake(['*' => Http::response(['entity' => 'collection', 'count' => 0, 'items' => []], 200)]);

        $result = $this->makeService()->fetchRecon(2026, 8);

        $this->assertSame(0, RazorpaySettlementTransaction::count());
        $this->assertSame(['entity' => 'collection', 'count' => 0, 'items' => []], $result);
    }
}
