<?php

namespace App\Services;

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


        // Inyecta customer_id en el body
        $body = array_merge($payload, [
            'customer_id' => $customerId,
        ]);

        return Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Idempotency-Key' => Str::uuid()->toString(),
            'Content-Type'    => 'application/json',
        ])
            ->post("{$this->baseUri}/virtual-accounts", $body)  // <-- endpoint corregido
            ->throw()
            ->json();

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
    public function generateTosLink(): array
    {
        //losg the request data
        Log::info('Bridge API Request', [
            'url'    => "{$this->baseUri}/customers/tos_links",
            'method' => 'POST',
            'data'   => [],
        ]);

        $callback = route('bridge.kyc.callback');
        $url = "{$this->baseUri}/customers/tos_links?redirect_uri=" . urlencode($callback);

        return Http::withHeaders([
            'Api-Key'         => $this->apiKey,
            'Idempotency-Key' => Str::uuid()->toString(),
            'Content-Type'    => 'application/json',
        ])->post($url)
            ->throw()
            ->json();
    }
}
