<?php

namespace App\Services;

use App\Casts\CustomerDto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class AlfredService
{
    protected $baseUri;
    protected $headers;

    public function __construct()
    {
        $this->baseUri = config('services.alfred.base_uri');
        $this->headers = array_filter([
            'Content-Type'     => 'application/json',
            'accept'         => 'application/json',
            'api-key'     => config('services.alfred.api_key'),
            'api-secret' => config('services.alfred.api_secret')
        ], static fn ($v) => !is_null($v) && $v !== '');
    }

    private function normalizeCustomerPayload(array $data): array
    {
        // Permite compatibilidad con payloads viejos (email/phoneNumber/country)
        // y soporta el nuevo contrato del API (/customer).
        $phone = $data['phone'] ?? $data['phoneNumber'] ?? null;

        $dob = $data['dateOfBirth'] ?? $data['dob'] ?? null;
        if (!empty($dob)) {
            try {
                $dob = Carbon::parse($dob)->toDateString();
            } catch (\Throwable $e) {
                // Si viene en formato ya válido, lo dejamos pasar tal cual
            }
        }

        return array_filter([
            'firstName'    => $data['firstName'] ?? $data['name'] ?? null,
            'lastName'     => $data['lastName'] ?? $data['surname'] ?? null,
            'email'        => $data['email'] ?? null,
            'phone'        => $phone,
            'address'      => $data['address'] ?? null,
            'city'         => $data['city'] ?? null,
            'country'      => $data['country'] ?? null,
            'postalCode'   => $data['postalCode'] ?? null,
            'dateOfBirth'  => $dob,
            'isActive'     => array_key_exists('isActive', $data) ? (bool) $data['isActive'] : null,
            'kycId'        => $data['kycId'] ?? null,
        ], static fn ($v) => !is_null($v));
    }

    private function customerDtoFromResponse(array $data): CustomerDto
    {
        $id = $data['customerId']
            ?? $data['id']
            ?? ($data['customer']['customerId'] ?? null)
            ?? ($data['customer']['id'] ?? null);

        $createdAt = $data['createdAt']
            ?? $data['created_at']
            ?? ($data['customer']['createdAt'] ?? null)
            ?? ($data['customer']['created_at'] ?? null)
            ?? now()->toISOString();

        if (empty($id)) {
            throw new \RuntimeException('Respuesta de customer sin id (customerId/id).');
        }

        return new CustomerDto((string) $id, (string) $createdAt);
    }

    private function unwrapCustomerSearchResponse(mixed $resp): mixed
    {
        // El API nuevo puede devolver { items: [...], meta: {...} }
        if (is_array($resp) && array_key_exists('items', $resp) && is_array($resp['items'])) {
            return $resp['items'];
        }
        return $resp;
    }

    // Customer - Crear (POST /customer)
    public function createCustomer(array $data): CustomerDto
    {
        $payload = $this->normalizeCustomerPayload($data);
        Log::info('Creating customer (POST /customer)', $payload);

        $resp = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/customer", $payload)
            ->throw()
            ->json();

        return $this->customerDtoFromResponse(is_array($resp) ? $resp : []);
    }

    /**
     * Crear customer devolviendo respuesta completa del API.
     * Maneja 409 (ya existe) devolviendo ['_conflict' => true, ...].
     */
    public function createCustomerRaw(array $data): array
    {
        $payload = $this->normalizeCustomerPayload($data);

        try {
            return Http::withHeaders($this->headers)
                ->post("{$this->baseUri}/customer", $payload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $status = $e->response?->status();
            if ($status === 409) {
                $body = $e->response?->json();
                return array_merge(['_conflict' => true], is_array($body) ? $body : []);
            }
            throw $e;
        }
    }

    // Customer - Obtener (GET /customer?...) y devolver el primer match como DTO
    public function GetCustomerByEmail(string $email): CustomerDto
    {
        $resp = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/customer", ['email' => $email])
            ->throw()
            ->json();

        $unwrapped = $this->unwrapCustomerSearchResponse($resp);

        // El API podría devolver un objeto o una lista. Tomamos el primer registro.
        if (is_array($unwrapped) && array_is_list($unwrapped)) {
            if (empty($unwrapped[0]) || !is_array($unwrapped[0])) {
                throw new \RuntimeException('Customer no encontrado para el email proporcionado.');
            }
            return $this->customerDtoFromResponse($unwrapped[0]);
        }

        if (is_array($unwrapped)) {
            return $this->customerDtoFromResponse($unwrapped);
        }

        throw new \RuntimeException('Formato de respuesta inválido al consultar customer.');
    }

    // Customer - Buscar (GET /customer?...) y devolver respuesta cruda (lista u objeto)
    public function searchCustomers(array $filters): array
    {
        // Soporta filtros del API: firstName,lastName,email,phone,isActive, etc.
        $query = array_filter([
            'firstName' => $filters['firstName'] ?? null,
            'lastName'  => $filters['lastName'] ?? null,
            'email'     => $filters['email'] ?? null,
            'phone'     => $filters['phone'] ?? ($filters['phoneNumber'] ?? null),
            'isActive'  => array_key_exists('isActive', $filters) ? $filters['isActive'] : null,
        ], static fn ($v) => !is_null($v) && $v !== '');

        return Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/customer", $query)
            ->throw()
            ->json();
    }

    // Customer - Actualizar (PUT /customer/{id})
    public function updateCustomer(string $customerId, array $data): array
    {
        $payload = $this->normalizeCustomerPayload($data);

        return Http::withHeaders($this->headers)
            ->put("{$this->baseUri}/customer/{$customerId}", $payload)
            ->throw()
            ->json();
    }

    /**
     * Compat: método viejo usado por el controller.
     * Antes llamaba a /customers/create; ahora es un alias de createCustomer.
     */
    public function createCustomerCountry(array $data): CustomerDto
    {
        return $this->createCustomer($data);
    }

    /**
     * Consulta el estado KYC de un customer y los campos pendientes.
     * Endpoint: GET /kyc/customer?customerId=...&onRampCountry=...&...
     */
    public function getKycCustomerStatus(string $customerId, array $params = []): array
    {
        $query = array_filter([
            'customerId'      => $customerId,
            'onRampCountry'   => $params['onRampCountry']   ?? 'US',
            'offRampCountry'  => $params['offRampCountry']  ?? 'DO',
            'onRampCurrency'  => $params['onRampCurrency']  ?? 'USD',
            'offRampCurrency' => $params['offRampCurrency'] ?? 'DOP',
            'role'            => $params['role']            ?? 'sender',
        ], static fn($v) => !is_null($v) && $v !== '');

        Log::debug('Alfred getKycCustomerStatus request', $query);

        $response = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/kyc/customer", $query)
            ->throw()
            ->json();

        Log::debug('Alfred getKycCustomerStatus response', is_array($response) ? $response : ['raw' => $response]);

        return is_array($response) ? $response : [];
    }

    public function KYC(string $customerId, array $fields, array $namedFiles = []): array
    {
        // Para multipart NO se debe forzar Content-Type: application/json
        $headers = $this->headers;
        unset($headers['Content-Type']);
        unset($headers['content-type']);

        $multipart = [];
        foreach ($fields as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $multipart[] = ['name' => (string) $key, 'contents' => (string) $value];
        }

        Log::debug('Alfred KYC request fields', array_column($multipart, 'contents', 'name'));
        Log::debug('Alfred KYC request files', array_keys($namedFiles));

        $req = Http::withHeaders($headers)->asMultipart();

        foreach ($namedFiles as $fieldName => $path) {
            if (!is_string($path) || $path === '' || !is_file($path)) {
                continue;
            }
            $req = $req->attach((string) $fieldName, fopen($path, 'r'), basename($path));
        }

        $response = $req
            ->post("{$this->baseUri}/kyc/customer/{$customerId}", $multipart)
            ->throw()
            ->json();

        Log::debug('Alfred KYC response', is_array($response) ? $response : ['raw' => $response]);

        return is_array($response) ? $response : [];
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
