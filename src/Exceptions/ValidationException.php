<?php

namespace Laraditz\Razorpay\Exceptions;

class ValidationException extends RazorpayException
{
    protected array $errors;

    public function __construct(string $message, array $errors = [], int $code = 0)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
