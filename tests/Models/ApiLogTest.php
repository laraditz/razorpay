<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Models\RazorpayApiLog;
use Laraditz\Razorpay\Tests\TestCase;

class ApiLogTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $apiLog = RazorpayApiLog::create([
            'method' => 'POST',
            'endpoint' => '/payment_links',
            'reference_id' => 'plink_test123',
            'request_payload' => ['amount' => 50000],
            'response_payload' => ['id' => 'plink_test123', 'status' => 'created'],
            'http_status' => 200,
            'duration_ms' => 123,
        ]);

        $apiLog->refresh();

        $this->assertSame('POST', $apiLog->method);
        $this->assertSame('/payment_links', $apiLog->endpoint);
        $this->assertSame('plink_test123', $apiLog->reference_id);
        $this->assertIsArray($apiLog->request_payload);
        $this->assertSame(['amount' => 50000], $apiLog->request_payload);
        $this->assertIsArray($apiLog->response_payload);
        $this->assertSame(['id' => 'plink_test123', 'status' => 'created'], $apiLog->response_payload);
        $this->assertSame(200, $apiLog->http_status);
        $this->assertSame(123, $apiLog->duration_ms);
    }
}
