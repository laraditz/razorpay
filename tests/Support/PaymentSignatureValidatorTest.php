<?php

namespace Laraditz\Razorpay\Tests\Support;

use Laraditz\Razorpay\Exceptions\AuthenticationException;
use Laraditz\Razorpay\Support\PaymentSignatureValidator;
use Laraditz\Razorpay\Tests\TestCase;

class PaymentSignatureValidatorTest extends TestCase
{
    public function test_valid_signature_passes(): void
    {
        config(['razorpay.key_secret' => 'test_key_secret']);

        $signature = hash_hmac('sha256', 'order_1|pay_1', 'test_key_secret');

        $this->assertTrue((new PaymentSignatureValidator())->verify('order_1', 'pay_1', $signature));
    }

    public function test_tampered_signature_fails(): void
    {
        config(['razorpay.key_secret' => 'test_key_secret']);

        $this->assertFalse((new PaymentSignatureValidator())->verify('order_1', 'pay_1', 'not-the-real-signature'));
    }

    public function test_missing_key_secret_throws_authentication_exception(): void
    {
        config(['razorpay.key_secret' => null]);

        $this->expectException(AuthenticationException::class);

        (new PaymentSignatureValidator())->verify('order_1', 'pay_1', 'anything');
    }
}
