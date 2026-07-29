<?php

namespace Laraditz\Razorpay\Support;

use Laraditz\Razorpay\Exceptions\WebhookException;

class SignatureValidator
{
    /**
     * Verify a Razorpay webhook signature.
     *
     * Razorpay signs the raw, unparsed request body with HMAC-SHA256
     * using the configured webhook secret.
     */
    public function verify(string $rawBody, string $signature): bool
    {
        $secret = config('razorpay.webhook_secret');

        if (empty($secret)) {
            throw new WebhookException('Razorpay webhook secret is not configured.');
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
