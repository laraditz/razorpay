<?php

namespace Laraditz\Razorpay\Tests;

class WebhookRouteTest extends TestCase
{
    public function test_webhook_route_is_registered_at_the_configured_path(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $body = json_encode(['event' => 'payment_link.paid']);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $response = $this->call('POST', config('razorpay.webhook_path'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(200);
    }

    public function test_webhook_route_is_not_csrf_protected(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $body = json_encode(['event' => 'payment_link.paid']);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        // No CSRF token supplied at all — a route inside the `web`
        // middleware group would reject this with 419.
        $response = $this->call('POST', config('razorpay.webhook_path'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $this->assertNotEquals(419, $response->getStatusCode());
    }
}
