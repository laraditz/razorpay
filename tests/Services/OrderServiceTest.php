<?php

namespace Laraditz\Razorpay\Tests\Services;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Enums\OrderStatus;
use Laraditz\Razorpay\Exceptions\ValidationException;
use Laraditz\Razorpay\Enums\RefundStatus;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Models\RazorpayRefund;
use Laraditz\Razorpay\Services\OrderService;
use Laraditz\Razorpay\Tests\TestCase;

class OrderServiceTest extends TestCase
{
    protected function makeService(): OrderService
    {
        return new OrderService(new RazorpayClient());
    }

    public function test_create_posts_to_orders_and_returns_array(): void
    {
        $responseBody = [
            'id' => 'order_EKwxwAgItmmXdp',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'status' => 'created',
            'attempts' => 0,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->create([
            'amount' => 50000,
            'currency' => 'MYR',
            'receipt' => 'receipt_1',
        ]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/orders'
                && $request->method() === 'POST'
                && $request['amount'] === 50000
                && $request['receipt'] === 'receipt_1';
        });
    }

    public function test_create_with_for_persists_subject_id_and_type(): void
    {
        $subject = RazorpayRefund::create([
            'razorpay_id' => 'rfnd_order_subject_test',
            'payment_id' => 'pay_1',
            'status' => RefundStatus::Pending,
            'amount' => 1000,
            'currency' => 'MYR',
        ]);

        Http::fake(['*' => Http::response(['id' => 'order_1', 'amount' => 50000, 'currency' => 'MYR', 'status' => 'created'], 200)]);

        $this->makeService()->for($subject)->create(['amount' => 50000, 'currency' => 'MYR']);

        $order = RazorpayOrder::where('razorpay_id', 'order_1')->first();

        $this->assertSame($subject->id, $order->subject_id);
        $this->assertSame($subject->getMorphClass(), $order->subject_type);
        $this->assertTrue($order->subject->is($subject));
    }

    public function test_create_without_for_leaves_subject_null(): void
    {
        Http::fake(['*' => Http::response(['id' => 'order_2', 'amount' => 50000, 'currency' => 'MYR', 'status' => 'created'], 200)]);

        $this->makeService()->create(['amount' => 50000, 'currency' => 'MYR']);

        $order = RazorpayOrder::where('razorpay_id', 'order_2')->first();

        $this->assertNull($order->subject_id);
        $this->assertNull($order->subject_type);
    }

    public function test_create_with_for_null_leaves_subject_null(): void
    {
        Http::fake(['*' => Http::response(['id' => 'order_3', 'amount' => 50000, 'currency' => 'MYR', 'status' => 'created'], 200)]);

        $this->makeService()->for(null)->create(['amount' => 50000, 'currency' => 'MYR']);

        $order = RazorpayOrder::where('razorpay_id', 'order_3')->first();

        $this->assertNull($order->subject_id);
        $this->assertNull($order->subject_type);
    }

    public function test_create_persists_a_local_order_record(): void
    {
        $responseBody = [
            'id' => 'order_EKwxwAgItmmXdp',
            'amount' => 50000,
            'amount_paid' => 0,
            'amount_due' => 50000,
            'currency' => 'MYR',
            'status' => 'created',
            'receipt' => 'receipt_1',
            'attempts' => 0,
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->create(['amount' => 50000, 'currency' => 'MYR']);

        $order = RazorpayOrder::where('razorpay_id', 'order_EKwxwAgItmmXdp')->first();

        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Created, $order->status);
        $this->assertSame(50000, $order->amount);
        $this->assertSame(0, $order->amount_paid);
        $this->assertSame(50000, $order->amount_due);
        $this->assertSame('MYR', $order->currency);
        $this->assertSame('receipt_1', $order->receipt);
        $this->assertSame(0, $order->attempts);
        $this->assertSame($responseBody, $order->raw_response);
    }

    public function test_fetch_gets_order_and_does_not_touch_local_record(): void
    {
        $responseBody = ['id' => 'order_EKwxwAgItmmXdp', 'status' => 'paid'];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetch('order_EKwxwAgItmmXdp');

        $this->assertSame($responseBody, $result);
        $this->assertSame(0, RazorpayOrder::count());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/orders/order_EKwxwAgItmmXdp'
                && $request->method() === 'GET';
        });
    }

    public function test_all_forwards_query_params_and_returns_list_envelope(): void
    {
        $responseBody = ['entity' => 'collection', 'count' => 1, 'items' => [['id' => 'order_1']]];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->all(['count' => 5, 'skip' => 0, 'receipt' => 'receipt_1']);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.razorpay.com/v1/orders?')
                && $request->method() === 'GET'
                && $request['count'] == 5
                && $request['receipt'] === 'receipt_1';
        });
    }

    public function test_update_patches_order_and_returns_array(): void
    {
        $responseBody = ['id' => 'order_EKwxwAgItmmXdp', 'notes' => ['key' => 'value']];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->update('order_EKwxwAgItmmXdp', ['notes' => ['key' => 'value']]);

        $this->assertSame($responseBody, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/orders/order_EKwxwAgItmmXdp'
                && $request->method() === 'PATCH';
        });
    }

    public function test_update_does_not_client_side_validate_fields(): void
    {
        Http::fake(['*' => Http::response(['error' => ['description' => 'invalid field']], 400)]);

        $this->expectException(ValidationException::class);

        $this->makeService()->update('order_EKwxwAgItmmXdp', ['amount' => 99999]);
    }

    public function test_fetch_payments_gets_payments_for_the_order(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [['id' => 'pay_1', 'status' => 'captured', 'amount' => 50000, 'currency' => 'MYR']],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $result = $this->makeService()->fetchPayments('order_EKwxwAgItmmXdp');

        $this->assertSame($responseBody, $result);
        $this->assertSame(0, RazorpayOrder::count());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/orders/order_EKwxwAgItmmXdp/payments'
                && $request->method() === 'GET';
        });
    }

    public function test_fetch_payments_syncs_every_returned_payment_locally(): void
    {
        $responseBody = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                ['id' => 'pay_1', 'status' => 'captured', 'amount' => 50000, 'currency' => 'MYR'],
                ['id' => 'pay_2', 'status' => 'failed', 'amount' => 20000, 'currency' => 'MYR'],
            ],
        ];

        Http::fake(['*' => Http::response($responseBody, 200)]);

        $this->makeService()->fetchPayments('order_EKwxwAgItmmXdp');

        $this->assertSame(2, RazorpayPayment::count());
        $this->assertNotNull(RazorpayPayment::where('razorpay_id', 'pay_1')->first());
        $this->assertNotNull(RazorpayPayment::where('razorpay_id', 'pay_2')->first());
    }

    public function test_verify_payment_signature_delegates_to_the_validator(): void
    {
        config(['razorpay.key_secret' => 'test_key_secret']);

        $validSignature = hash_hmac('sha256', 'order_1|pay_1', 'test_key_secret');

        $this->assertTrue($this->makeService()->verifyPaymentSignature('order_1', 'pay_1', $validSignature));
        $this->assertFalse($this->makeService()->verifyPaymentSignature('order_1', 'pay_1', 'wrong-signature'));
    }
}
