<?php

namespace Laraditz\Razorpay\Tests\Client\Concerns;

use Laraditz\Razorpay\Client\Concerns\HandlesAuthentication;
use Laraditz\Razorpay\Exceptions\AuthenticationException;
use Laraditz\Razorpay\Tests\TestCase;

class HandlesAuthenticationTest extends TestCase
{
    protected function makeSubject()
    {
        return new class {
            use HandlesAuthentication;

            public function authHeaders(): array
            {
                return $this->getAuthHeaders();
            }
        };
    }

    public function test_it_builds_basic_auth_header_from_key_id_and_secret(): void
    {
        config(['razorpay.key_id' => 'rzp_test_abc', 'razorpay.key_secret' => 'shh']);

        $headers = $this->makeSubject()->authHeaders();

        $this->assertSame(
            'Basic ' . base64_encode('rzp_test_abc:shh'),
            $headers['Authorization']
        );
    }

    public function test_missing_key_id_throws_authentication_exception(): void
    {
        config(['razorpay.key_id' => null, 'razorpay.key_secret' => 'shh']);

        $this->expectException(AuthenticationException::class);

        $this->makeSubject()->authHeaders();
    }

    public function test_missing_key_secret_throws_authentication_exception(): void
    {
        config(['razorpay.key_id' => 'rzp_test_abc', 'razorpay.key_secret' => null]);

        $this->expectException(AuthenticationException::class);

        $this->makeSubject()->authHeaders();
    }
}
