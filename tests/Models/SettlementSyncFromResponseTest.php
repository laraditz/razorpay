<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\SettlementStatus;
use Laraditz\Razorpay\Models\RazorpaySettlement;
use Laraditz\Razorpay\Tests\TestCase;

class SettlementSyncFromResponseTest extends TestCase
{
    protected function makeResponse(array $overrides = []): array
    {
        return array_merge([
            'id' => 'setl_1',
            'amount' => 100000,
            'fees' => 500,
            'tax' => 100,
            'utr' => 'UTR1',
            'status' => 'processed',
            'created_at' => now()->timestamp,
        ], $overrides);
    }

    public function test_processed_status_with_created_at_sets_settled_at(): void
    {
        $settlement = RazorpaySettlement::syncFromResponse($this->makeResponse());

        $this->assertNotNull($settlement);
        $this->assertSame(SettlementStatus::Processed, $settlement->status);
        $this->assertNotNull($settlement->settled_at);
    }

    public function test_non_processed_status_leaves_settled_at_null(): void
    {
        $settlement = RazorpaySettlement::syncFromResponse($this->makeResponse(['status' => 'created']));

        $this->assertNotNull($settlement);
        $this->assertSame(SettlementStatus::Created, $settlement->status);
        $this->assertNull($settlement->settled_at);
    }

    public function test_created_at_zero_does_not_produce_an_epoch_date(): void
    {
        $settlement = RazorpaySettlement::syncFromResponse($this->makeResponse(['created_at' => 0]));

        $this->assertNotNull($settlement);
        $this->assertNull($settlement->settled_at);
    }

    public function test_missing_id_returns_null_and_writes_nothing(): void
    {
        $result = RazorpaySettlement::syncFromResponse(['status' => 'processed']);

        $this->assertNull($result);
        $this->assertSame(0, RazorpaySettlement::count());
    }

    public function test_syncing_same_id_updates_instead_of_duplicating(): void
    {
        RazorpaySettlement::syncFromResponse($this->makeResponse(['status' => 'created']));
        RazorpaySettlement::syncFromResponse($this->makeResponse(['status' => 'processed']));

        $this->assertSame(1, RazorpaySettlement::count());
        $this->assertSame(SettlementStatus::Processed, RazorpaySettlement::first()->status);
    }
}
