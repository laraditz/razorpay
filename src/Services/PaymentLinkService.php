<?php

namespace Laraditz\Razorpay\Services;

use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\PaymentLink;

class PaymentLinkService
{
    protected RazorpayClient $client;

    public function __construct(RazorpayClient $client)
    {
        $this->client = $client;
    }

    public function create(array $data): array
    {
        $response = $this->client->post('/payment_links', $data);

        $this->storeLocalRecord($response);

        return $response;
    }

    public function fetch(string $id): array
    {
        return $this->client->get("/payment_links/{$id}");
    }

    public function update(string $id, array $data): array
    {
        return $this->client->patch("/payment_links/{$id}", $data);
    }

    protected function storeLocalRecord(array $response): PaymentLink
    {
        return PaymentLink::create([
            'razorpay_id' => $response['id'] ?? null,
            'order_id' => $response['order_id'] ?? null,
            'status' => $response['status'] ?? null,
            'amount' => $response['amount'] ?? null,
            'amount_paid' => $response['amount_paid'] ?? null,
            'currency' => $response['currency'] ?? null,
            'reference_id' => $response['reference_id'] ?? null,
            'description' => $response['description'] ?? null,
            'customer_name' => $response['customer']['name'] ?? null,
            'customer_email' => $response['customer']['email'] ?? null,
            'customer_contact' => $response['customer']['contact'] ?? null,
            'notify_sms' => $response['notify']['sms'] ?? false,
            'notify_email' => $response['notify']['email'] ?? false,
            'reminder_enable' => $response['reminder_enable'] ?? false,
            'accept_partial' => $response['accept_partial'] ?? false,
            'first_min_partial_amount' => $response['first_min_partial_amount'] ?? null,
            'notes' => $response['notes'] ?? null,
            'callback_url' => $response['callback_url'] ?? null,
            'callback_method' => $response['callback_method'] ?? null,
            'short_url' => $response['short_url'] ?? null,
            'raw_response' => $response,
            'expire_by' => isset($response['expire_by']) ? \Carbon\Carbon::createFromTimestamp($response['expire_by']) : null,
        ]);
    }
}
