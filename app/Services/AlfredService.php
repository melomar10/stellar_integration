<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AlfredService
{
    protected $baseUri;
    protected $headers;

    public function __construct()
    {
        $this->baseUri = config('services.alfred.base_uri');
        $this->headers = [
            'Content-Type'     => 'application/json',
            'accept'         => 'application/json',
            'api-key'     => config('services.alfred.api_key'),
            'api-secret' => config('services.alfred.api_secret')
        ];
    }

    // 1. Crear Customer
    public function createCustomer(array $data): array
    {
        Log::info('Creating customer', $data);
        return Http::withHeaders($this->headers)->post("{$this->baseUri}/customers", [
            'type' =>  'INDIVIDUAL',
            'email' =>  $data['email'],
            "phoneNumber" =>  $data['phoneNumber'],
        ])->throw()->json();
    }

    // 1.1 Crear el Country de Customer
    public function createCustomerCountry(array $data): array
    {
        return Http::withHeaders($this->headers)->post("{$this->baseUri}/customers/create", [
            'type' =>  'INDIVIDUAL',
            'email' =>  $data['email'],
            "phoneNumber" =>  $data['phoneNumber'],
            'country' => $data['country'],
        ])->throw()->json();
    }

    // 2. Listar requisitos KYC por país
    public function getKycRequirements(string $country): array
    {
        return Http::withHeaders($this->headers)->get("{$this->baseUri}/kycRequirements", [
            'country' => $country
        ])->throw()->json();
    }

    // 3. Agregar información KYC
    public function addKycInfo(string $customerId, array $kycData): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/customers/{$customerId}/kyc", $kycData)
            ->throw()->json();
    }

    // 4. Agregar archivos KYC (ARG & MEX)
    public function uploadKycFile(string $customerId, string $submissionId, string $filePath): array
    {
        return Http::withHeaders($this->headers)
            ->attach('file', fopen($filePath, 'r'))
            ->post("{$this->baseUri}/customers/{$customerId}/kyc/{$submissionId}/files")
            ->throw()->json();
    }

    // 5. Enviar KYC
    public function submitKyc(string $customerId, string $submissionId): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/customers/{$customerId}/kyc/{$submissionId}/submit")
            ->throw()->json();
    }

    // 6. Crear Quote
    public function createQuote(array $payload): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/quotes", $payload)
            ->throw()->json();
    }

    // 7. Onramp
    public function createOnramp(array $payload): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/onramp", $payload)
            ->throw()->json();
    }

    // 8. Offramp
    public function createOfframp(array $payload): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/offramp", $payload)
            ->throw()->json();
    }
    // 9. Create Payment Method
    public function createPaymentMethod(array $payload): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/fiatAccounts", $payload)
            ->throw()->json();
    }
    // 10. Listar requisitos KYC por país
    public function getPaymentMethods(string $customerId): array
    {
        return Http::withHeaders($this->headers)->get("{$this->baseUri}/fiatAccounts", [
            'customerId' => $customerId
        ])->throw()->json();
    }
    // 11. Soporte
    public function createSupportTicket(array $payload): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/support", $payload)
            ->throw()->json();
    }
}
