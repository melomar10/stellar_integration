<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Casts\CustomerDto;
use App\Casts\FiatAccountDto;
use App\Casts\QuoteResponseDto;
use App\Casts\KycSubmissionDto;
use App\Casts\FiatAccountResponseDto;
use App\Casts\QuoteFeeDto;
use App\Casts\QuoteMetadataDto;

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
          $this->headersFile = [
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
   public function GetCustomerByEmail(string $email): CustomerDto
    {
            $data = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/customers/find/{$email}")
            ->throw()
            ->json();

        $first = $data[0] ?? null;

        if (!$first) {
            throw new \Exception('No se encontró el cliente');
        }

        return new CustomerDto(
            $first['customerId'],
            $first['createdAt'],
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

    // 2. Listar requisitos KYC por país
    public function getKYCSubmission(string $customerId): array
    {
        return Http::withHeaders($this->headers)->get("{$this->baseUri}/customers/kyc/{$customerId}",)->throw()->json();
    }

    public function getKYCStatus(string $customerId, string $submissionId): array
    {
        return Http::withHeaders($this->headers)->get("{$this->baseUri}/customers/{$customerId}/kyc/{$submissionId}/status",)->throw()->json();
    }

      // obtener informacion del kyc por usuario 
    public function getKYCInfo(string $customerId): array
    {
        return Http::withHeaders($this->headers)->get("{$this->baseUri}/customers/{$customerId}",)->throw()->json();
    }

    public function getKYCVerification(string $customerId): array
    {
        return Http::withHeaders($this->headers)->get("{$this->baseUri}/customers/{$customerId}/verification/url",)->throw()->json();
    }

  //editar la informacion brindada por el usuario en el kyc 
    public function updateKYCInfo(array $data): array
    {
        return Http::withHeaders($this->headers)
            ->put("{$this->baseUri}/customers/kyc", $data)
            ->throw()
            ->json();
    }

    // 3. Agregar información KYC
     public function addKycInfo(string $customerId, array $kycData): KycSubmissionDto
    {
        $data = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/customers/{$customerId}/kyc", [
                'kycSubmission' => $kycData,
            ])
            ->throw()
            ->json();

        return new KycSubmissionDto(
            $data['submissionId'],
            $data['createdAt'],
            $data['firstName'] ?? null,
            $data['lastName'] ?? null,
            $data['dateOfBirth'] ?? null,
            $data['country'] ?? null,
            $data['city'] ?? null,
            $data['zipCode'] ?? null,
            $data['address'] ?? null,
            $data['state'] ?? null,
            $data['nationalities'] ?? [],
            $data['phoneNumber'] ?? null,
            $data['occupation'] ?? null,
            $data['email'] ?? null
        );
    }

    public function uploadKycFile(string $customerId, string $submissionId, string $filePath, string $fileType, string $fileName = null): array
    {
        return Http::withHeaders($this->headersFile)
            ->asMultipart()
            ->attach('fileBody', fopen($filePath, 'r'), $fileName ?? basename($filePath))
            ->post("{$this->baseUri}/customers/{$customerId}/kyc/{$submissionId}/files", [
                'fileType' => $fileType
            ])
            ->throw()
            ->json();
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

    public function createWebhooks(array $payload): array
    {
         return Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/webhooks", $payload)
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
    public function getPaymentMethods(string $customerId): FiatAccountResponseDto
    {
            $response = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/fiatAccounts", [
                'customerId' => $customerId
            ])
            ->throw()
            ->json();
            
        $data = $response[0]; // toma el primer método de pago

        return new FiatAccountResponseDto(
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
            'fromCurrency'       => 'USDC',
            'toCurrency'         => 'DOP',
            'chain'              => 'XLM',
            'fromAmount'         => $data['amount'],
            'paymentMethodType'  => 'BANK',
            'metadata'           => [],
            'toAmount'           => '',
        ]);

        // 4. Ejecutar Offramp
        $offramp = $this->createOfframp([
            'quoteId' => $quote->quoteId,
            'customerId' => $customer->customerId,
            'fiatAccountId' => $paymentMethod->fiatAccountId,
            'chain' => 'XLM',
            'fromCurrency' => 'USDC',
            'toCurrency' => 'DOP',
            'amount' => $data['amount'],
            'originAddress' => $data['originAddress'],
        ]);

        return $offramp;
    }
}
