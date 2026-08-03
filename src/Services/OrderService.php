<?php

namespace Laraditz\Razorpay\Services;

use Illuminate\Database\Eloquent\Model;
use Laraditz\Razorpay\Client\RazorpayClient;
use Laraditz\Razorpay\Models\RazorpayOrder;
use Laraditz\Razorpay\Models\RazorpayPayment;
use Laraditz\Razorpay\Support\PaymentSignatureValidator;

class OrderService
{
    protected RazorpayClient $client;
    protected PaymentSignatureValidator $signatureValidator;
    protected ?Model $subject = null;

    public function __construct(RazorpayClient $client, ?PaymentSignatureValidator $signatureValidator = null)
    {
        $this->client = $client;
        $this->signatureValidator = $signatureValidator ?? new PaymentSignatureValidator();
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        return $this->signatureValidator->verify($orderId, $paymentId, $signature);
    }

    /**
     * Attach the order about to be created to any model in the consuming
     * app, via a polymorphic subject_id/subject_type pair.
     */
    public function for(?Model $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function create(array $data): array
    {
        $response = $this->client->post('/orders', $data);

        $order = RazorpayOrder::syncFromResponse($response);

        $order?->update([
            'subject_id' => $this->subject?->getKey(),
            'subject_type' => $this->subject?->getMorphClass(),
        ]);

        return $response;
    }

    public function fetch(string $id): array
    {
        $response = $this->client->get("/orders/{$id}");

        RazorpayOrder::syncFromResponse($response);

        return $response;
    }

    public function all(array $query = []): array
    {
        $response = $this->client->get('/orders', $query);

        foreach ($response['items'] ?? [] as $item) {
            RazorpayOrder::syncFromResponse($item);
        }

        return $response;
    }

    public function update(string $id, array $data): array
    {
        $response = $this->client->patch("/orders/{$id}", $data);

        RazorpayOrder::syncFromResponse($response);

        return $response;
    }

    public function fetchPayments(string $id): array
    {
        $response = $this->client->get("/orders/{$id}/payments");

        foreach ($response['items'] ?? [] as $item) {
            RazorpayPayment::syncFromResponse($item);
        }

        return $response;
    }
}
