<?php

namespace Laraditz\Razorpay\Client\Concerns;

use Laraditz\Razorpay\Exceptions\AuthenticationException;

trait HandlesAuthentication
{
    protected function getKeyId(): string
    {
        $keyId = config('razorpay.key_id');

        if (empty($keyId)) {
            throw new AuthenticationException('Razorpay key_id is not configured. Please set RAZORPAY_KEY_ID in your .env file.');
        }

        return $keyId;
    }

    protected function getKeySecret(): string
    {
        $keySecret = config('razorpay.key_secret');

        if (empty($keySecret)) {
            throw new AuthenticationException('Razorpay key_secret is not configured. Please set RAZORPAY_KEY_SECRET in your .env file.');
        }

        return $keySecret;
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->getKeyId() . ':' . $this->getKeySecret()),
        ];
    }
}
