<?php

namespace Laraditz\Razorpay\Tests\Client\Concerns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Exceptions\RazorpayException;
use Laraditz\Razorpay\Models\RazorpayApiLog;
use Laraditz\Razorpay\Tests\TestCase;

class LogsApiCallsTest extends TestCase
{
    public function test_get_call_creates_an_api_log_row(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->get('/payment_links/plink_1');

        $apiLog = RazorpayApiLog::first();

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

        $apiLog = RazorpayApiLog::first();

        $this->assertNotNull($apiLog);
        $this->assertSame('POST', $apiLog->method);
        $this->assertSame('/payment_links', $apiLog->endpoint);
        $this->assertSame(200, $apiLog->http_status);
    }

    public function test_reference_id_is_extracted_from_response_id(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_ExjpAUN3gVHrPJ', 'status' => 'created'], 200)]);

        (new RazorpayClient())->post('/payment_links', ['amount' => 50000]);

        $apiLog = RazorpayApiLog::first();

        $this->assertSame('plink_ExjpAUN3gVHrPJ', $apiLog->reference_id);
    }

    public function test_reference_id_is_null_for_list_envelope_response(): void
    {
        Http::fake(['*' => Http::response(['entity' => 'collection', 'count' => 0, 'items' => []], 200)]);

        (new RazorpayClient())->get('/payment_links');

        $apiLog = RazorpayApiLog::first();

        $this->assertNull($apiLog->reference_id);
    }

    public function test_no_log_row_created_when_logging_disabled(): void
    {
        config(['razorpay.log_api_calls' => false]);

        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->get('/payment_links/plink_1');

        $this->assertSame(0, RazorpayApiLog::count());
    }

    public function test_log_row_created_when_logging_enabled_by_default(): void
    {
        Http::fake(['*' => Http::response(['id' => 'plink_1'], 200)]);

        (new RazorpayClient())->get('/payment_links/plink_1');

        $this->assertSame(1, RazorpayApiLog::count());
    }

    public function test_customer_pii_is_redacted_in_request_and_response(): void
    {
        $customer = ['name' => 'John Doe', 'email' => 'john@example.com', 'contact' => '+60123456789'];

        Http::fake(['*' => Http::response(['id' => 'plink_1', 'customer' => $customer], 200)]);

        (new RazorpayClient())->post('/payment_links', ['amount' => 50000, 'customer' => $customer]);

        $apiLog = RazorpayApiLog::first();

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

        $apiLog = RazorpayApiLog::first();

        $this->assertArrayNotHasKey('name', $apiLog->response_payload['customer']);
        $this->assertArrayHasKey('email', $apiLog->response_payload['customer']);
    }

    public function test_payloads_without_customer_field_pass_through_unchanged(): void
    {
        Http::fake(['*' => Http::response(['id' => 'order_1', 'amount' => 50000, 'receipt' => 'receipt_1'], 200)]);

        (new RazorpayClient())->post('/orders', ['amount' => 50000, 'receipt' => 'receipt_1']);

        $apiLog = RazorpayApiLog::first();

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

        $apiLog = RazorpayApiLog::first();
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

        $apiLog = RazorpayApiLog::first();

        $this->assertSame(['id' => 'order_1', 'amount' => 50000], $apiLog->response_payload['items'][0]);
    }

    public function test_non_2xx_response_still_logs_before_exception_propagates(): void
    {
        Http::fake(['*' => Http::response(['error' => ['description' => 'invalid amount']], 400)]);

        try {
            (new RazorpayClient())->post('/payment_links', ['amount' => -1]);
            $this->fail('Expected an exception to be thrown.');
        } catch (RazorpayException $e) {
            // expected
        }

        $apiLog = RazorpayApiLog::first();

        $this->assertNotNull($apiLog);
        $this->assertSame(400, $apiLog->http_status);
        $this->assertSame(['error' => ['description' => 'invalid amount']], $apiLog->response_payload);
        $this->assertSame(['amount' => -1], $apiLog->request_payload);
    }

    public function test_connection_failure_logs_with_null_response(): void
    {
        Http::fake(['*' => function () {
            throw new ConnectionException('Could not connect to host.');
        }]);

        try {
            (new RazorpayClient())->post('/payment_links', ['amount' => 50000]);
            $this->fail('Expected a ConnectionException to be thrown.');
        } catch (ConnectionException $e) {
            // expected
        }

        $apiLog = RazorpayApiLog::first();

        $this->assertNotNull($apiLog);
        $this->assertNull($apiLog->http_status);
        $this->assertNull($apiLog->response_payload);
        $this->assertSame(['amount' => 50000], $apiLog->request_payload);
    }
}
