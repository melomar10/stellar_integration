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
    public function createCustomer(array $data): CustomerDto
    {
        Log::info('Creating customer', $data);
       $data = Http::withHeaders($this->headers)->post("{$this->baseUri}/customers", [
            'type' =>  'INDIVIDUAL',
            'email' =>  $data['email'],
            "phoneNumber" =>  $data['phoneNumber'],
        ])->throw()->json();
        
         return new CustomerDto(
            $data['customerId'],
            $data['createdAt'],
        );
    }
   public function GetCustomerByEmail(string $email): FiatAccountDto
    {
            $data = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/customers/find/{$email}")
            ->throw()
            ->json();

        return new CustomerDto(
            $data['customerId'],
            $data['createdAt'],
        );
    }
    // 1.1 Crear el Country de Customer
    public function createCustomerCountry(array $data): CustomerDto
    {
         $data = Http::withHeaders($this->headers)->post("{$this->baseUri}/customers/create", [
            'type' =>  'INDIVIDUAL',
            'email' =>  $data['email'],
            "phoneNumber" =>  $data['phoneNumber'],
            'country' => $data['country'],
        ])->throw()->json();

        return new CustomerDto(
            $data['customerId'],
            $data['createdAt'],
        );
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
    public function createQuote(array $payload): QuoteResponseDto
    {
        $data = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/quotes", $payload)
            ->throw()->json();

              $fees = array_map(fn ($f) => new QuoteFeeDto($f['type'], $f['amount'], $f['currency']), $data['fees']);

            $metadata = new QuoteMetadataDto(
                $data['metadata']['developerId'],
                $data['metadata']['markupFeeRate']
            );

            return new QuoteResponseDto(
                $data['quoteId'],
                $data['fromCurrency'],
                $data['toCurrency'],
                $data['fromAmount'],
                $data['toAmount'],
                $data['expiration'],
                $fees,
                $data['rate'],
                $metadata
            );
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
    public function createPaymentMethod(array $payload): FiatAccountDto
    {
       $data = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/fiatAccounts", $payload)
            ->throw()->json();

         return new FiatAccountDto(
            $data['fiatAccountId'],
            $data['type'],
            $data['accountNumber'],
            $data['accountType'],
            $data['createdAt']
        );
    }

    // 10. Listar requisitos KYC por país
    public function getPaymentMethods(string $customerId): FiatAccountDto
    {
            $data = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/fiatAccounts", [
                'customerId' => $customerId
            ])
            ->throw()
            ->json();

        return new FiatAccountDto(
            $data['fiatAccountId'],
            $data['type'],
            $data['accountNumber'],
            $data['accountType'],
            $data['bankName'],
            $data['createdAt']
        );
    }
    // 11. Soporte
    public function createSupportTicket(array $payload): array
    {
        return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/support", $payload)
            ->throw()->json();
    }

    public function handleOfframpDomi(array $data): array
    {
        // 1. Verificar o crear Customer
        try {
            $customer = $this->GetCustomerByEmail($data['email'] ?? '');
        } catch (\Throwable $e) {
            $customer = $this->createCustomer([
                'email' => $data['email'] ?? null,
                'phoneNumber' => $data['phoneNumber'],
            ]);
            $customer = $this->createCustomerCountry([
                'email' => $data['email'] ?? null,
                'phoneNumber' => $data['phoneNumber'],
                'country' => $data['country'] ?? null,
            ]);
        }

        // 2. Obtener o crear método de pago
        try {
            $paymentMethod = $this->getPaymentMethods($customer->customerId);
        } catch (\Throwable $e) {
            $paymentMethod = $this->createPaymentMethod([
                'customerId' => $customer->customerId,
                'type' => 'ACH_DOM',
                'accountNumber' => $data['accountNumber'],
                'accountType' => $data['accountType'],
            ]);
        }

        // 3. Crear quote
        $quote = $this->createQuote([
            'fromCurrency' => $data['fromCurrency'],
            'toCurrency' => $data['toCurrency'],
            'chain' => $data['chain'],
            'fromAmount' => $data['amount'],
            'toAmount' => $data['amount'],
            'paymentMethodType' => 'BANK',
        ]);

        // 4. Ejecutar Offramp
        $offramp = $this->createOfframp([
            'quoteId' => $quote->quoteId,
            'customerId' => $customer->customerId,
            'fiatAccountId' => $paymentMethod->fiatAccountId,
            'chain' => $data['chain'],
            'fromCurrency' => $data['fromCurrency'],
            'toCurrency' => $data['toCurrency'],
            'amount' => $data['amount'],
        ]);

        return $offramp;
    }
    public function handleOfframp(array $data): array
    {
        // 1. Verificar o crear Customer
        try {
            $customer = $this->GetCustomerByEmail($data['email'] ?? '');
        } catch (\Throwable $e) {
            $customer = $this->createCustomer([
                'email' => $data['email'] ?? null,
                'phoneNumber' => $data['phoneNumber'],
            ]);
            $customer = $this->createCustomerCountry([
                'email' => $data['email'] ?? null,
                'phoneNumber' => $data['phoneNumber'],
                'country' => $data['country'] ?? null,
            ]);
        }

        // 2. Obtener o crear método de pago
        try {
            $paymentMethod = $this->getPaymentMethods($customer->customerId);
        } catch (\Throwable $e) {
            $paymentMethod = $this->createPaymentMethod([
                'customerId' => $customer->customerId,
                'type' => 'ACH_DOM',
                'accountNumber' => $data['accountNumber'],
                'accountType' => $data['accountType'],
            ]);
        }

        // 3. Crear quote
        $quote = $this->createQuote([
            'fromCurrency' => $data['fromCurrency'],
            'toCurrency' => $data['toCurrency'],
            'chain' => $data['chain'],
            'fromAmount' => $data['amount'],
            'toAmount' => $data['amount'],
            'paymentMethodType' => 'BANK',
        ]);

        // 4. Ejecutar Offramp
        $offramp = $this->createOfframp([
            'quoteId' => $quote->quoteId,
            'customerId' => $customer->customerId,
            'fiatAccountId' => $paymentMethod->fiatAccountId,
            'chain' => $data['chain'],
            'fromCurrency' => $data['fromCurrency'],
            'toCurrency' => $data['toCurrency'],
            'amount' => $data['amount'],
        ]);

        return $offramp;
    }
}
