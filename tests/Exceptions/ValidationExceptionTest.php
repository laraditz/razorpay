<?php

namespace Laraditz\Razorpay\Tests\Exceptions;

use Laraditz\Razorpay\Exceptions\RazorpayException;
use Laraditz\Razorpay\Exceptions\ValidationException;
use Laraditz\Razorpay\Tests\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function test_it_extends_razorpay_exception_and_returns_errors(): void
    {
        $errors = ['field' => 'amount', 'description' => 'amount is required'];
        $exception = new ValidationException('validation failed', $errors, 400);

        $this->assertInstanceOf(RazorpayException::class, $exception);
        $this->assertSame('validation failed', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame($errors, $exception->getErrors());
    }

    public function test_errors_default_to_empty_array(): void
    {
        $exception = new ValidationException('validation failed');

        $this->assertSame([], $exception->getErrors());
    }
}
