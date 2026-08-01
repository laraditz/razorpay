<?php

namespace Laraditz\Razorpay\Client;

use Laraditz\Razorpay\Client\Concerns\HandlesAuthentication;
use Laraditz\Razorpay\Client\Concerns\HandlesErrors;
use Laraditz\Razorpay\Client\Concerns\MakesHttpRequests;
use Laraditz\Razorpay\Client\Contracts\ClientInterface;

class RazorpayClient implements ClientInterface
{
    use HandlesAuthentication;
    use HandlesErrors;
    use MakesHttpRequests;

    public function get(string $endpoint, array $query = [], array $headers = []): array
    {
        return $this->request('get', $endpoint, $query, $headers);
    }

    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('post', $endpoint, $data, $headers);
    }

    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('put', $endpoint, $data, $headers);
    }

    public function patch(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->request('patch', $endpoint, $data, $headers);
    }

    public function delete(string $endpoint, array $headers = []): array
    {
        return $this->request('delete', $endpoint, [], $headers);
    }

    protected function request(string $method, string $endpoint, array $data, array $headers): array
    {
        $response = $this->buildClient()->withHeaders($headers)->{$method}($endpoint, $data);

        return $this->handleResponse($response);
    }
}
