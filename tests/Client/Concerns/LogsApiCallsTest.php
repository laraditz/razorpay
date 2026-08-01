<?php

namespace Laraditz\Razorpay\Tests\Client\Concerns;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\ApiLog;
use Laraditz\Razorpay\Tests\TestCase;

class LogsApiCallsTest extends TestCase
{
    public function test_get_call_creates_an_api_log_row(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->get('/payment_links/plink_1');

        $apiLog = ApiLog::first();

        $this->assertNotNull($apiLog);
        $this->assertSame('GET', $apiLog->method);
        $this->assertSame('/payment_links/plink_1', $apiLog->endpoint);
        $this->assertSame(200, $apiLog->http_status);
        $this->assertIsInt($apiLog->duration_ms);
        $this->assertGreaterThanOrEqual(0, $apiLog->duration_ms);
    }

    public function test_post_call_creates_an_api_log_row(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->post('/payment_links', ['amount' => 50000]);

        $apiLog = ApiLog::first();

        $this->assertNotNull($apiLog);
        $this->assertSame('POST', $apiLog->method);
        $this->assertSame('/payment_links', $apiLog->endpoint);
        $this->assertSame(200, $apiLog->http_status);
    }
}
