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

    public function test_reference_id_is_extracted_from_response_id(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_ExjpAUN3gVHrPJ', 'status' => 'created'], 200)]);

        (new RazorpayClient())->post('/payment_links', ['amount' => 50000]);

        $apiLog = ApiLog::first();

        $this->assertSame('plink_ExjpAUN3gVHrPJ', $apiLog->reference_id);
    }

    public function test_reference_id_is_null_for_list_envelope_response(): void
    {
        Http::fake(['*' => Http::response(['entity' => 'collection', 'count' => 0, 'items' => []], 200)]);

        (new RazorpayClient())->get('/payment_links');

        $apiLog = ApiLog::first();

        $this->assertNull($apiLog->reference_id);
    }
}
