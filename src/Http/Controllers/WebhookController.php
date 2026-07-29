<?php

namespace Laraditz\Razorpay\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    /**
     * Handle incoming Razorpay webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success'], 200);
    }
}
