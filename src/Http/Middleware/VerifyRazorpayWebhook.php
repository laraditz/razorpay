<?php

namespace Laraditz\Razorpay\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laraditz\Razorpay\Support\SignatureValidator;
use Symfony\Component\HttpFoundation\Response;

class VerifyRazorpayWebhook
{
    protected SignatureValidator $validator;

    public function __construct(SignatureValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Razorpay-Signature');

        if (empty($signature) || !$this->validator->verify($request->getContent(), $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        return $next($request);
    }
}
