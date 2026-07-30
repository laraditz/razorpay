<?php

namespace Laraditz\Razorpay\Support;

use Laraditz\Razorpay\Exceptions\AuthenticationException;

class PaymentSignatureValidator
{
    /**
     * Verify a Razorpay Checkout payment signature.
     *
     * Razorpay signs "{order_id}|{payment_id}" with HMAC-SHA256
     * using the account's key_secret.
     */
    public function verify(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = config('razorpay.key_secret');

        if (empty($secret)) {
            throw new AuthenticationException('Razorpay key_secret is not configured. Please set RAZORPAY_KEY_SECRET in your .env file.');
        }

        $expected = hash_hmac('sha256', "{$orderId}|{$paymentId}", $secret);

        return hash_equals($expected, $signature);
    }
}
