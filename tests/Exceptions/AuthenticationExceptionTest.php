<?php

namespace Laraditz\Razorpay\Tests\Exceptions;

use Laraditz\Razorpay\Exceptions\AuthenticationException;
use Laraditz\Razorpay\Exceptions\RazorpayException;
use Laraditz\Razorpay\Tests\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    public function test_it_extends_razorpay_exception_and_passes_message(): void
    {
        $exception = new AuthenticationException('missing key_id', 401);

        $this->assertInstanceOf(RazorpayException::class, $exception);
        $this->assertSame('missing key_id', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
    }
}
