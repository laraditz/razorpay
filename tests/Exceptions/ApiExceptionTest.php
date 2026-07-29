<?php

namespace Laraditz\Razorpay\Tests\Exceptions;

use Laraditz\Razorpay\Exceptions\ApiException;
use Laraditz\Razorpay\Exceptions\RazorpayException;
use Laraditz\Razorpay\Tests\TestCase;

class ApiExceptionTest extends TestCase
{
    public function test_it_extends_razorpay_exception_and_returns_response(): void
    {
        $body = ['error' => ['code' => 'SERVER_ERROR', 'description' => 'boom']];
        $exception = new ApiException('server error', 500, $body);

        $this->assertInstanceOf(RazorpayException::class, $exception);
        $this->assertSame('server error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($body, $exception->getResponse());
    }

    public function test_response_defaults_to_empty_array(): void
    {
        $exception = new ApiException('server error');

        $this->assertSame([], $exception->getResponse());
    }
}
