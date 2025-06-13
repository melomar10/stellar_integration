<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BridgeService
{
    protected string $baseUri;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUri = config('services.bridge.base_uri');
        $this->apiKey  = config('services.bridge.api_key');
    }

    /** 1. Create Customer (KYC/KYB) */
    public function createCustomer(array $data): array
    {
        //losg the request data
        Log::info('Bridge API Request', [
            'url'    => "{$this->baseUri}/customers",
            'method' => 'POST',
            'data'   => $data,
        ]);

        $response = Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Idempotency-Key' => Str::uuid()->toString(),
            'Content-Type'    => 'application/json',
        ])->post("{$this->baseUri}/customers", $data);

        //Logs the response
        Log::info('Bridge API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return $response->throw()->json();
    }

    /** 2. Generate KYC Link (hosted flow) */
    public function generateKycLink(array $data): array
    {
        //losg the request data
        Log::info('Bridge API Request', [
            'url'    => "{$this->baseUri}/kyc_links",
            'method' => 'POST',
            'data'   => $data,
        ]);

        $response = Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Idempotency-Key' => Str::uuid()->toString(),
            'Content-Type'    => 'application/json',
        ])->post("{$this->baseUri}/kyc_links", $data);

        Log::info('Bridge API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response->throw()->json();
    }

    /** 3. Create Virtual Account */
    public function createVirtualAccount(string $customerId, array $payload): array
    {
        Log::info('Bridge API Request', [
            'url'    => "{$this->baseUri}/customers/{$customerId}/virtual_accounts",
            'method' => 'POST',
            'data'   => $payload,
        ]);

        $response = Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Idempotency-Key' => Str::uuid()->toString(),
            'Content-Type'    => 'application/json',
        ])->post("{$this->baseUri}/customers/{$customerId}/virtual_accounts", $payload);

        Log::info('Bridge API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response->throw()->json();
    }

    /**
     * 4. Create Transfer
     */
    public function createTransfer(array $data): array
    {
        //losg the request data
        Log::info('Bridge API Request', [
            'url'    => "{$this->baseUri}/transfers",
            'method' => 'POST',
            'data'   => $data,
        ]);

        $response = Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Idempotency-Key' => Str::uuid()->toString(),
            'Content-Type'    => 'application/json',
        ])->post("{$this->baseUri}/transfers", $data)
            ->throw()
            ->json();

        Log::info('Bridge API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response;
    }
    // 5. Generate ToS Link for new customer
    public function generateTosLink($id): array
    {
        $customer = Customer::find($id);
        if (!$customer || !$customer->bridge_customer_id) {
            throw new \Exception('Customer not found or has no bridge_customer_id');
        }

        Log::info('Bridge API Request', [
            'url'    => "{$this->baseUri}/customers/{$customer->bridge_customer_id}/tos_acceptance_link",
            'method' => 'GET',
            'data'   => [],
        ]);

        $response = Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Content-Type'    => 'application/json',
        ])->get("{$this->baseUri}/customers/{$customer->bridge_customer_id}/tos_acceptance_link");

        Log::info('Bridge API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response->throw()->json();
    }
}
