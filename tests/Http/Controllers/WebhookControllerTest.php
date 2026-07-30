<?php

namespace Laraditz\Razorpay\Tests\Http\Controllers;

use Laraditz\Razorpay\Support\WebhookHandler;
use Laraditz\Razorpay\Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    protected function postWebhook(array $payload)
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        return $this->call('POST', config('razorpay.webhook_path'), [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_valid_webhook_returns_200_with_success_shape(): void
    {
        $response = $this->postWebhook(['event' => 'payment_link.paid']);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function test_handler_exception_returns_500(): void
    {
        $this->app->bind(WebhookHandler::class, function () {
            return new class extends WebhookHandler {
                public function handle(array $payload): void
                {
                    throw new \Exception('boom');
                }
            };
        });

        $response = $this->postWebhook(['event' => 'payment_link.paid']);

        $response->assertStatus(500);
        $response->assertJson(['status' => 'error']);
    }
}
