<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\SettlementStatus;
use Laraditz\Razorpay\Models\RazorpaySettlement;
use Laraditz\Razorpay\Services\SettlementService;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    protected function makeService(): SettlementService
    {
        return new SettlementService(new RazorpayClient());
    }

    public function test_fetch_gets_settlement_and_syncs_local_record(): void
    {
        $responseBody = ['id' => 'setl_1', 'status' => 'processed', 'amount' => 100000, 'utr' => 'UTR1', 'created_at' => now()->timestamp];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetch('setl_1');

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/settlements/setl_1'
                && $request->method() === 'GET';
        });

        $settlement = RazorpaySettlement::where('razorpay_id', 'setl_1')->first();
        $this->assertNotNull($settlement);
        $this->assertSame(SettlementStatus::Processed, $settlement->status);
    }

    public function test_all_forwards_query_params_and_syncs_every_item(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                ['id' => 'setl_1', 'status' => 'processed', 'amount' => 100000, 'created_at' => now()->timestamp],
                ['id' => 'setl_2', 'status' => 'processed', 'amount' => 50000, 'created_at' => now()->timestamp],
            ],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->all(['count' => 5]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.razorpay.com/v1/settlements?')
                && $request->method() === 'GET'
                && $request['count'] == 5;
        });

        $this->assertSame(2, RazorpaySettlement::count());
        $this->assertNotNull(RazorpaySettlement::where('razorpay_id', 'setl_1')->first());
        $this->assertNotNull(RazorpaySettlement::where('razorpay_id', 'setl_2')->first());
    }
}
