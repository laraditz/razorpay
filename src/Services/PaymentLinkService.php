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

    public function cancel(string $id): array
    {
        $response = $this->client->post("/payment_links/{$id}/cancel");

        $paymentLink = PaymentLink::where('razorpay_id', $id)->first();

        if ($paymentLink) {
            $paymentLink->update([
                'status' => $response['status'] ?? $paymentLink->status,
                'cancelled_at' => $this->nullableTimestamp($response['cancelled_at'] ?? null) ?? now(),
            ]);
        }

        return $response;
    }

    public function all(array $query = []): array
    {
        return $this->client->get('/payment_links', $query);
    }

    public function notifyBy(string $id, string $medium): array
    {
        return $this->client->post("/payment_links/{$id}/notify_by/{$medium}");
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
            'expire_by' => $this->nullableTimestamp($response['expire_by'] ?? null),
        ]);
    }

    /**
     * Razorpay returns 0 (not null) for unset timestamp fields — 0 is a
     * valid Unix timestamp (1970-01-01 00:00:00), which is out of range for
     * a MySQL TIMESTAMP column, so it must be treated as "no value".
     */
    protected function nullableTimestamp(?int $timestamp): ?\Carbon\Carbon
    {
        return $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : null;
    }
}
