<?php

namespace Laraditz\Razorpay\Tests\Support;

use Laraditz\Razorpay\Exceptions\WebhookException;
use Laraditz\Razorpay\Support\SignatureValidator;
use Laraditz\Razorpay\Tests\TestCase;

class SignatureValidatorTest extends TestCase
{
    public function test_valid_signature_passes(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $body = json_encode(['event' => 'payment_link.paid']);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $this->assertTrue((new SignatureValidator())->verify($body, $signature));
    }

    public function test_tampered_body_fails(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $signature = hash_hmac('sha256', json_encode(['event' => 'payment_link.paid']), 'whsec_test');

        $this->assertFalse((new SignatureValidator())->verify(json_encode(['event' => 'payment_link.cancelled']), $signature));
    }

    public function test_tampered_signature_fails(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $body = json_encode(['event' => 'payment_link.paid']);

        $this->assertFalse((new SignatureValidator())->verify($body, 'not-the-real-signature'));
    }

    public function test_missing_secret_throws_webhook_exception(): void
    {
        config(['razorpay.webhook_secret' => null]);

        $this->expectException(WebhookException::class);

        (new SignatureValidator())->verify('{}', 'anything');
    }
}
