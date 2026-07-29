<?php

namespace Laraditz\Razorpay\Tests\Exceptions;

use Laraditz\Razorpay\Exceptions\RazorpayException;
use Laraditz\Razorpay\Tests\TestCase;

class RazorpayExceptionTest extends TestCase
{
    public function test_it_extends_exception_and_is_throwable(): void
    {
        $exception = new RazorpayException('something went wrong', 500);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('something went wrong', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());

        $this->expectException(RazorpayException::class);
        throw $exception;
    }
}
