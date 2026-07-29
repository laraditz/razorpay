<?php

namespace Laraditz\Razorpay\Tests\Client\Concerns;

use Illuminate\Support\Facades\Http;
use Laraditz\Razorpay\Client\Concerns\HandlesAuthentication;
use Laraditz\Razorpay\Client\Concerns\MakesHttpRequests;
use Laraditz\Razorpay\Tests\TestCase;

class MakesHttpRequestsTest extends TestCase
{
    protected function makeSubject()
    {
        return new class {
            use HandlesAuthentication;
            use MakesHttpRequests;

            public function client()
            {
                return $this->buildClient();
            }
        };
    }

    public function test_it_builds_client_with_base_url_headers_and_timeout(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        config([
            'razorpay.key_id' => 'rzp_test_abc',
            'razorpay.key_secret' => 'shh',
            'razorpay.base_url' => 'https://api.razorpay.com/v1',
            'razorpay.timeout' => 15,
        ]);

        $this->makeSubject()->client()->get('/payment_links');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.razorpay.com/v1/payment_links'
                && $request->hasHeader('Authorization', 'Basic ' . base64_encode('rzp_test_abc:shh'))
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->hasHeader('Accept', 'application/json');
        });
    }
}
