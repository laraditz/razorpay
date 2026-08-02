<?php

namespace Laraditz\Razorpay\Tests\Http\Middleware;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laraditz\Razorpay\Http\Middleware\VerifyRazorpayWebhook;
use Laraditz\Razorpay\Tests\TestCase;

class VerifyRazorpayWebhookTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::post('/test-razorpay-webhook', function () {
            return response()->json(['reached' => true]);
        })->middleware(VerifyRazorpayWebhook::class);
    }

    public function test_invalid_signature_is_rejected_with_401(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $response = $this->postJson('/test-razorpay-webhook', ['event' => 'payment_link.paid'], [
            'X-Razorpay-Signature' => 'not-the-real-signature',
        ]);

        $response->assertStatus(401);
    }

    public function test_missing_signature_header_is_rejected_with_401(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $response = $this->postJson('/test-razorpay-webhook', ['event' => 'payment_link.paid']);

        $response->assertStatus(401);
    }

    public function test_invalid_signature_logs_a_warning_with_signature_present_true(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Razorpay webhook signature verification failed'
                    && $context['signature_header_present'] === true
                    && array_key_exists('remote_ip', $context)
                    && $context['raw_body'] === '{"event":"payment_link.paid"}';
            });

        $this->call('POST', '/test-razorpay-webhook', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'not-the-real-signature',
            'CONTENT_TYPE' => 'application/json',
        ], '{"event":"payment_link.paid"}');
    }

    public function test_missing_signature_logs_a_warning_with_signature_present_false(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Razorpay webhook signature verification failed'
                    && $context['signature_header_present'] === false
                    && array_key_exists('remote_ip', $context)
                    && $context['raw_body'] === '{"event":"payment_link.paid"}';
            });

        $this->call('POST', '/test-razorpay-webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"event":"payment_link.paid"}');
    }

    public function test_valid_signature_passes_through(): void
    {
        config(['razorpay.webhook_secret' => 'whsec_test']);

        $body = json_encode(['event' => 'payment_link.paid']);
        $signature = hash_hmac('sha256', $body, 'whsec_test');

        $response = $this->call('POST', '/test-razorpay-webhook', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(200);
        $response->assertJson(['reached' => true]);
    }
}
