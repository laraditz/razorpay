<?php

namespace Laraditz\Razorpay\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            Log::warning('Razorpay webhook signature verification failed', [
                'signature_header_present' => !empty($signature),
                'remote_ip' => $request->ip(),
                'raw_body' => $request->getContent(),
            ]);

            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        return $next($request);
    }
}
