<?php

namespace Laraditz\Razorpay\Tests\Models;

use Laraditz\Razorpay\Enums\PaymentStatus;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentTest extends TestCase
{
    public function test_it_casts_fields_correctly(): void
    {
        $payment = RazorpayPayment::create([
            'razorpay_id' => 'pay_test123',
            'order_id' => 'order_test123',
            'status' => PaymentStatus::Captured,
            'method' => 'card',
            'amount' => 50000,
            'currency' => 'MYR',
            'captured' => true,
            'notes' => ['foo' => 'bar'],
            'raw_response' => ['id' => 'pay_test123', 'status' => 'captured'],
        ]);

        $payment->refresh();

        $this->assertSame('pay_test123', $payment->razorpay_id);
        $this->assertSame('order_test123', $payment->order_id);
        $this->assertInstanceOf(PaymentStatus::class, $payment->status);
        $this->assertSame(PaymentStatus::Captured, $payment->status);
        $this->assertSame('card', $payment->method);
        $this->assertSame(50000, $payment->amount);
        $this->assertTrue($payment->captured);
        $this->assertIsArray($payment->notes);
        $this->assertSame(['foo' => 'bar'], $payment->notes);
        $this->assertIsArray($payment->raw_response);
        $this->assertSame(['id' => 'pay_test123', 'status' => 'captured'], $payment->raw_response);
    }
}
