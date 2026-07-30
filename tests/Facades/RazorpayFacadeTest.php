<?php

namespace Laraditz\Razorpay\Tests\Facades;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Facades\Razorpay;
use Laraditz\Razorpay\Tests\TestCase;

class RazorpayFacadeTest extends TestCase
{
    public function test_facade_resolves_end_to_end_to_create_a_payment_link(): void
    {
        $responseBody = ['id' => 'plink_ExjpAUN3gVHrPJ', 'status' => 'created', 'amount' => 50000, 'currency' => 'MYR'];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = Razorpay::paymentLink()->create(['amount' => 50000, 'currency' => 'MYR']);

        $this->assertSame($responseBody, $result);
    }
}
