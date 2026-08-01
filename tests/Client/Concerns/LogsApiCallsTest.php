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

    public function test_no_log_row_created_when_logging_disabled(): void
    {
        config(['razorpay.log_api_calls' => false]);

        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->get('/payment_links/plink_1');

        $this->assertSame(0, ApiLog::count());
    }

    public function test_log_row_created_when_logging_enabled_by_default(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->get('/payment_links/plink_1');

        $this->assertSame(1, ApiLog::count());
    }

    public function test_customer_pii_is_redacted_in_request_and_response(): void
    {
        $customer = ['name' => 'John Doe', 'email' => 'john@example.com', 'contact' => '+60123456789'];

        Http::fake(['*' => Http::response(['id' => 'plink_1', 'customer' => $customer], 200)]);

        (new RazorpayClient())->post('/payment_links', ['amount' => 50000, 'customer' => $customer]);

        $apiLog = ApiLog::first();

        foreach (['request_payload', 'response_payload'] as $payloadField) {
            $redactedCustomer = $apiLog->{$payloadField}['customer'];

            $this->assertMatchesRegularExpression('/^\[redacted:[a-f0-9]{64}\]$/', $redactedCustomer['name']);
            $this->assertMatchesRegularExpression('/^\[redacted:[a-f0-9]{64}\]$/', $redactedCustomer['email']);
            $this->assertMatchesRegularExpression('/^\[redacted:[a-f0-9]{64}\]$/', $redactedCustomer['contact']);

            $expectedHash = hash_hmac('sha256', 'John Doe', config('app.key'));
            $this->assertSame("[redacted:{$expectedHash}]", $redactedCustomer['name']);
        }
    }

    public function test_missing_customer_fields_stay_absent_not_null(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_1', 'customer' => ['email' => 'john@example.com']], 200)]);

        (new RazorpayClient())->post('/payment_links', ['amount' => 50000, 'customer' => ['email' => 'john@example.com']]);

        $apiLog = ApiLog::first();

        $this->assertArrayNotHasKey('name', $apiLog->response_payload['customer']);
        $this->assertArrayHasKey('email', $apiLog->response_payload['customer']);
    }

    public function test_payloads_without_customer_field_pass_through_unchanged(): void
    {
        Http::fake(['*' => Http::response(['id' => 'order_1', 'amount' => 50000, 'receipt' => 'receipt_1'], 200)]);

        (new RazorpayClient())->post('/orders', ['amount' => 50000, 'receipt' => 'receipt_1']);

        $apiLog = ApiLog::first();

        $this->assertSame(['amount' => 50000, 'receipt' => 'receipt_1'], $apiLog->request_payload);
        $this->assertSame(['id' => 'order_1', 'amount' => 50000, 'receipt' => 'receipt_1'], $apiLog->response_payload);
    }

    public function test_customer_pii_is_redacted_in_each_item_of_a_list_response(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                ['id' => 'plink_1', 'customer' => ['name' => 'John Doe', 'email' => 'john@example.com']],
                ['id' => 'plink_2', 'customer' => ['name' => 'Jane Roe', 'email' => 'jane@example.com']],
            ],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        (new RazorpayClient())->get('/payment_links');

        $apiLog = ApiLog::first();
        $items = $apiLog->response_payload['items'];

        $expectedJohnHash = hash_hmac('sha256', 'John Doe', config('app.key'));
        $expectedJaneHash = hash_hmac('sha256', 'Jane Roe', config('app.key'));

        $this->assertSame("[redacted:{$expectedJohnHash}]", $items[0]['customer']['name']);
        $this->assertSame("[redacted:{$expectedJaneHash}]", $items[1]['customer']['name']);
        $this->assertMatchesRegularExpression('/^\[redacted:[a-f0-9]{64}\]$/', $items[0]['customer']['email']);
        $this->assertMatchesRegularExpression('/^\[redacted:[a-f0-9]{64}\]$/', $items[1]['customer']['email']);
    }

    public function test_list_response_items_without_customer_are_left_as_is(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                ['id' => 'order_1', 'amount' => 50000],
            ],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        (new RazorpayClient())->get('/orders');

        $apiLog = ApiLog::first();

        $this->assertSame(['id' => 'order_1', 'amount' => 50000], $apiLog->response_payload['items'][0]);
    }
}
