<?php

namespace App\Services;

use App\Casts\CustomerDto;
use App\Models\AlfredAccount;
use App\Models\AlfredOrder;
use App\Models\AlfredQuote;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Valida el estado KYC de un customer (aprobado, pendiente, etc.).
     * Endpoint: GET /kyc/customer/{customerId}/status?onRampCountry=...&offRampCountry=...&...
     *
     * Respuesta típica: id, status, fullName, statusVerifiedAt, createdAt, updatedAt.
     */
    public function getKycCustomerStatus(string $customerId, array $params = []): array
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            throw new \InvalidArgumentException('customerId es requerido para consultar el estado KYC.');
        }

        $query = array_filter([
            'onRampCountry'   => $params['onRampCountry']   ?? 'US',
            'offRampCountry'  => $params['offRampCountry']  ?? 'DO',
            'onRampCurrency'  => $params['onRampCurrency']  ?? 'USD',
            'offRampCurrency' => $params['offRampCurrency'] ?? 'DOP',
            'role'            => $params['role']            ?? 'sender',
        ], static fn ($v) => !is_null($v) && $v !== '');

        $url = rtrim((string) $this->baseUri, '/') . '/kyc/customer/' . rawurlencode($customerId) . '/status';

        Log::debug('Alfred getKycCustomerStatus request', ['url' => $url, 'query' => $query]);

        $response = Http::withHeaders($this->headers)
            ->get($url, $query)
            ->throw()
            ->json();

        Log::debug('Alfred getKycCustomerStatus response', is_array($response) ? $response : ['raw' => $response]);

        return is_array($response) ? $response : [];
    }

    /**
     * Envía el KYC como multipart.
     * $filePaths es un array indexado de rutas absolutas; todos se envían bajo el campo 'files'.
     */
    public function KYC(string $customerId, array $fields, array $filePaths = []): array
    {
        $headers = $this->headers;
        unset($headers['Content-Type'], $headers['content-type']);

        $multipart = [];

        foreach ($fields as $key => $value) {
            if (is_null($value)) continue;
            if (is_bool($value))                  $value = $value ? 'true' : 'false';
            if (is_array($value) || is_object($value)) $value = json_encode($value);
            $multipart[] = ['name' => (string) $key, 'contents' => (string) $value];
        }

        // Todos los archivos van bajo el campo 'files' según contrato del API de Alfred
        foreach ($filePaths as $path) {
            if (!is_string($path) || $path === '' || !is_file($path)) continue;
            $multipart[] = [
                'name'     => 'files',
                'contents' => fopen($path, 'r'),
                'filename' => basename($path),
            ];
        }

        Log::debug('Alfred KYC request fields', array_column(
            array_filter($multipart, fn($p) => !is_resource($p['contents'])),
            'contents', 'name'
        ));
        Log::debug('Alfred KYC request file count', ['count' => count($filePaths)]);

        $response = Http::withHeaders($headers)
            ->asMultipart()
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

    /**
     * Crea una cotización fiat (POST /quotes) para transferencias indirectas.
     * Solo varían fromAmount y customerId; el resto del cuerpo es fijo según contrato Alfred.
     * Persiste quoteId, fromAmount, rate, toAmount, expiration, onRampExternalQuoteId, offRampExternalQuoteId.
     */
    public function createTransferQuote(string $customerId, float|string $amount): AlfredQuote
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            throw new \InvalidArgumentException('customerId es requerido.');
        }

        $fromAmount = is_numeric($amount)
            ? (float) $amount
            : (float) preg_replace('/[^\d.\-]/', '', (string) $amount);

        if ($fromAmount <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor que 0.');
        }

        $payload = [
            'fromCurrency'             => 'USD',
            'fromCurrencyIsCrypto'     => false,
            'toCurrency'               => 'DOP',
            'toCurrencyIsCrypto'       => false,
            'fromAmount'               => $fromAmount,
            'paymentMethod'            => 'BANK',
            'originCountryId'          => '3be84950-6336-4ec1-b95a-ba818d74a4c8',
            'destinationCountryId'     => '801cce87-c2b5-4e8b-9c83-ddf1a98b9fdc',
            'customerId'               => $customerId,
        ];

        Log::info('Alfred POST /quotes (transfer quote)', ['customerId' => $customerId, 'fromAmount' => $fromAmount]);


        $data = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/quotes", $payload)
            ->throw()
            ->json();

            // crea un log para el payload
        Log::debug('Alfred POST /quotes (transfer quote) payload', ['payload' => $payload]);

        if (empty($data['quoteId'])) {
            throw new \RuntimeException('Respuesta de /quotes sin quoteId.');
        }

        return AlfredQuote::create([
            'alfred_customer_id'           => $customerId,
            'quote_id'                     => (string) $data['quoteId'],
            'status'                       => AlfredQuote::STATUS_PENDING,
            'from_amount'                  => $data['fromAmount'] ?? $fromAmount,
            'rate'                         => isset($data['rate']) ? (string) $data['rate'] : null,
            'to_amount'                    => isset($data['toAmount']) ? (string) $data['toAmount'] : null,
            'expiration'                   => isset($data['expiration']) ? (int) $data['expiration'] : null,
            'on_ramp_external_quote_id'    => isset($data['onRampExternalQuoteId']) ? (string) $data['onRampExternalQuoteId'] : null,
            'off_ramp_external_quote_id'   => isset($data['offRampExternalQuoteId']) ? (string) $data['offRampExternalQuoteId'] : null,
        ]);
    }

    /**
     * Crea orden por teléfonos: resuelve customer IDs, usa la última quote pending del sender.
     *
     * @return array{order_id: string, total_amount: string|null, currency: string|null, quote_id: string, order: AlfredOrder}
     */
    public function createOrderByPhones(string $phoneSender, string $phoneReceiver): array
    {
        $senderCustomerId = $this->resolveAlfredCustomerIdByPhone($phoneSender);
        if ($senderCustomerId === null) {
            throw new \RuntimeException('No se encontró customer ID de Alfred para el teléfono del remitente (phone_sender).');
        }

        $receiverCustomerId = $this->resolveAlfredCustomerIdByPhone($phoneReceiver);
        if ($receiverCustomerId === null) {
            throw new \RuntimeException('No se encontró customer ID de Alfred para el teléfono del destinatario (phone_receiver).');
        }

        $quote = AlfredQuote::where('alfred_customer_id', $senderCustomerId)
            ->where('status', AlfredQuote::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();

        if (!$quote) {
            throw new \RuntimeException('El remitente no tiene una cotización pendiente. Cree una quote antes de la orden.');
        }

        $result = $this->createOrder($quote->quote_id, $senderCustomerId, $receiverCustomerId);
        $result['quote_id'] = $quote->quote_id;

        return $result;
    }

    /**
     * Crea una orden (POST /orders). Parámetros: quoteId, senderCustomerId, receiverCustomerId.
     * initiatingCustomerId = senderCustomerId. Persiste la orden en estado processed y marca la quote como completed.
     *
     * @return array{order_id: string, total_amount: string, currency: string|null, order: AlfredOrder}
     */
    public function createOrder(string $quoteId, string $senderCustomerId, string $receiverCustomerId): array
    {
        $quoteId = trim($quoteId);
        $senderCustomerId = trim($senderCustomerId);
        $receiverCustomerId = trim($receiverCustomerId);

        if ($quoteId === '' || $senderCustomerId === '' || $receiverCustomerId === '') {
            throw new \InvalidArgumentException('quoteId, senderCustomerId y receiverCustomerId son requeridos.');
        }

        $quote = AlfredQuote::where('quote_id', $quoteId)->first();
        if (!$quote) {
            throw new \RuntimeException("No existe una cotización local con quote_id {$quoteId}.");
        }

        $payload = [
            'quoteId'                => $quoteId,
            'initiatingCustomerId'   => $senderCustomerId,
            'senderCustomerId'       => $senderCustomerId,
            'receiverCustomerId'     => $receiverCustomerId,
            'metadata'               => [
                'source'   => 'mobile_app',
                'campaign' => 'summer_2024',
            ],
            'useEscrow'              => true,
        ];

        Log::info('Alfred POST /orders', $payload);

        $data = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/orders", $payload)
            ->throw()
            ->json();

        if (empty($data['id'])) {
            throw new \RuntimeException('Respuesta de /orders sin id.');
        }

        $totalAmount = is_array($data['totalAmount'] ?? null) ? $data['totalAmount'] : [];
        $totalValue = $totalAmount['value'] ?? null;
        $totalCurrency = $totalAmount['currency'] ?? null;
        $totalPrecision = isset($totalAmount['precision']) ? (int) $totalAmount['precision'] : null;

        return DB::transaction(function () use ($data, $quote, $quoteId, $senderCustomerId, $receiverCustomerId, $totalValue, $totalCurrency, $totalPrecision) {
            $order = AlfredOrder::create([
                'alfred_quote_id'          => $quote->id,
                'alfred_order_id'          => (string) $data['id'],
                'quote_id'                 => $quoteId,
                'initiating_customer_id'   => $senderCustomerId,
                'sender_customer_id'       => $senderCustomerId,
                'receiver_customer_id'     => $receiverCustomerId,
                'status'                   => AlfredOrder::STATUS_PROCESSED,
                'api_status'               => $data['status'] ?? null,
                'sub_status'               => $data['subStatus'] ?? null,
                'total_amount_value'       => $totalValue !== null ? (string) $totalValue : null,
                'total_amount_currency'    => $totalCurrency,
                'total_amount_precision'   => $totalPrecision,
                'external_order_id'        => $data['externalOrderId'] ?? null,
                'metadata'                 => $data['metadata'] ?? null,
                'use_escrow'               => (bool) ($data['useEscrow'] ?? true),
                'error_message'            => $data['errorMessage'] ?? null,
                'error_code'               => $data['errorCode'] ?? null,
                'alfred_created_at'        => $data['createdAt'] ?? null,
                'alfred_updated_at'        => $data['updatedAt'] ?? null,
                'alfred_completed_at'      => $data['completedAt'] ?? null,
                'links'                    => $data['_links'] ?? null,
            ]);

            $quote->update(['status' => AlfredQuote::STATUS_COMPLETED]);

            $formattedTotal = $totalValue !== null && $totalValue !== ''
                ? number_format((float) $totalValue, 2, '.', '')
                : null;

            return [
                'order_id'     => (string) $data['id'],
                'total_amount' => $formattedTotal,
                'currency'     => $totalCurrency,
                'order'        => $order,
            ];
        });
    }

    /**
     * Confirma orden: GET /orders/{orderId}/instructions y devuelve paymentInstructions del sender.
     *
     * @return array{
     *     order_id: string,
     *     payment_method: string|null,
     *     payment_instructions: array<string, mixed>,
     *     links: array<int, mixed>|null
     * }
     */
    public function getOrderInstructions(string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new \InvalidArgumentException('orderId es requerido.');
        }

        Log::info('Alfred GET /orders/{id}/instructions', ['orderId' => $orderId]);

        $data = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/orders/{$orderId}/instructions")
            ->throw()
            ->json();

        $instructions = is_array($data['instructions'] ?? null) ? $data['instructions'] : [];
        $sender = is_array($instructions['sender'] ?? null) ? $instructions['sender'] : [];
        $paymentInstructions = is_array($sender['paymentInstructions'] ?? null)
            ? $sender['paymentInstructions']
            : [];

        if (isset($paymentInstructions['amount']) && $paymentInstructions['amount'] !== '') {
            $paymentInstructions['amount'] = number_format((float) $paymentInstructions['amount'], 2, '.', '');
        }

        return [
            'order_id'               => $orderId,
            'payment_method'         => $sender['paymentMethod'] ?? null,
            'payment_instructions'   => $paymentInstructions,
            'links'                  => $data['_links'] ?? null,
        ];
    }

    /**
     * Busca la última orden pendiente del sender por teléfono y obtiene instrucciones de pago.
     *
     * @return array{
     *     ok: true,
     *     phone: string,
     *     order_id: string,
     *     payment_method: string|null,
     *     payment_instructions: array<string, mixed>
     * }
     */
    public function confirmOrderByPhone(string $phone): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            throw new \InvalidArgumentException('Teléfono inválido.');
        }

        $senderCustomerId = $this->resolveAlfredCustomerIdByPhone($phone);
        if ($senderCustomerId === null) {
            throw new \RuntimeException('No se encontró customer ID de Alfred para el teléfono indicado.');
        }

        $order = AlfredOrder::where('sender_customer_id', $senderCustomerId)
            ->where('api_status', 'pending')
            ->where('status', AlfredOrder::STATUS_PROCESSED)
            ->orderByDesc('id')
            ->first();

        if (!$order) {
            throw new \RuntimeException('No hay una orden pendiente de confirmación para este teléfono.');
        }

        $instructions = $this->getOrderInstructions($order->alfred_order_id);

        $order->update([
            'status'               => AlfredOrder::STATUS_CONFIRMED,
            'payment_instructions' => $instructions['payment_instructions'],
            'links'                => $instructions['links'] ?? $order->links,
        ]);

        return [
            'phone'                  => $normalizedPhone,
            'order_id'               => $instructions['order_id'],
            'payment_method'         => $instructions['payment_method'],
            'payment_instructions'   => $instructions['payment_instructions'],
        ];
    }

    /**
     * GET /customer/{customerId}/bank-details
     *
     * @return list<array<string, mixed>>
     */
    public function getCustomerBankDetails(string $customerId): array
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            throw new \InvalidArgumentException('customerId es requerido.');
        }

        $data = Http::withHeaders($this->headers)
            ->get("{$this->baseUri}/customer/{$customerId}/bank-details")
            ->throw()
            ->json();

        return is_array($data) ? $data : [];
    }

    /**
     * POST /customer/{customerId}/bank-details
     */
    public function createCustomerBankDetail(string $customerId, array $payload): array
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            throw new \InvalidArgumentException('customerId es requerido.');
        }

        $body = $this->filterBankDetailPayload($payload);

        Log::info('Alfred POST bank-details', ['customerId' => $customerId]);

        $data = Http::withHeaders($this->headers)
            ->post("{$this->baseUri}/customer/{$customerId}/bank-details", $body)
            ->throw()
            ->json();

        return is_array($data) ? $data : [];
    }

    /**
     * PUT /customer/{customerId}/bank-details/{id}
     */
    public function updateCustomerBankDetail(string $customerId, string $bankDetailId, array $payload): array
    {
        $customerId = trim($customerId);
        $bankDetailId = trim($bankDetailId);
        if ($customerId === '' || $bankDetailId === '') {
            throw new \InvalidArgumentException('customerId y bankDetailId son requeridos.');
        }

        $body = $this->filterBankDetailPayload($payload);

        $data = Http::withHeaders($this->headers)
            ->put("{$this->baseUri}/customer/{$customerId}/bank-details/{$bankDetailId}", $body)
            ->throw()
            ->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Deja exactamente una cuenta con isDefault true en Alfred (resto en false).
     *
     * @return list<array<string, mixed>>
     */
    public function setDefaultCustomerBankDetail(string $customerId, string $bankDetailId): array
    {
        $bankDetailId = trim($bankDetailId);
        $accounts = $this->getCustomerBankDetails($customerId);

        if ($bankDetailId === '') {
            throw new \InvalidArgumentException('bankDetailId es requerido.');
        }

        $accountIds = [];
        foreach ($accounts as $account) {
            $id = trim((string) ($account['id'] ?? ''));
            if ($id !== '') {
                $accountIds[] = ['id' => $id, 'data' => $account];
            }
        }

        $found = false;
        foreach ($accountIds as $row) {
            if ($row['id'] === $bankDetailId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \RuntimeException('Cuenta bancaria no encontrada.');
        }

        // Paso 1: quitar predeterminada de todas
        foreach ($accountIds as $row) {
            $payload = $this->bankDetailPayloadFromAccount($row['data']);
            $payload['isDefault'] = false;
            $this->updateCustomerBankDetail($customerId, $row['id'], $payload);
        }

        // Paso 2: marcar solo la cuenta elegida como predeterminada
        foreach ($accountIds as $row) {
            if ($row['id'] !== $bankDetailId) {
                continue;
            }
            $payload = $this->bankDetailPayloadFromAccount($row['data']);
            $payload['isDefault'] = true;
            $this->updateCustomerBankDetail($customerId, $row['id'], $payload);
            break;
        }

        $refreshed = $this->getCustomerBankDetails($customerId);

        return $this->sortBankDetailsDefaultFirst(
            $this->enforceSingleDefaultAccount($refreshed, $bankDetailId)
        );
    }

    /**
     * @return array{phone: string, customer_id: string, accounts: list<array<string, mixed>>}
     */
    public function getBankDetailsByPhone(string $phone): array
    {
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            throw new \InvalidArgumentException('Teléfono inválido.');
        }

        $customerId = $this->resolveAlfredCustomerIdByPhone($phone);
        if ($customerId === null) {
            throw new \RuntimeException('No se encontró un cliente con ese teléfono en Alfred.');
        }

        $accounts = $this->sortBankDetailsDefaultFirst(
            $this->enforceSingleDefaultAccount(
                $this->getCustomerBankDetails($customerId)
            )
        );

        return [
            'phone'        => $normalizedPhone,
            'customer_id'  => $customerId,
            'accounts'     => $accounts,
        ];
    }

    /**
     * Garantiza un solo isDefault true en la lista (útil si el API devolvió varias).
     *
     * @param  list<array<string, mixed>>  $accounts
     * @return list<array<string, mixed>>
     */
    private function enforceSingleDefaultAccount(array $accounts, ?string $preferredDefaultId = null): array
    {
        $preferredDefaultId = $preferredDefaultId !== null ? trim($preferredDefaultId) : null;
        $chosenId = null;

        if ($preferredDefaultId !== null && $preferredDefaultId !== '') {
            foreach ($accounts as $account) {
                if (trim((string) ($account['id'] ?? '')) === $preferredDefaultId) {
                    $chosenId = $preferredDefaultId;
                    break;
                }
            }
        }

        if ($chosenId === null) {
            foreach ($accounts as $account) {
                if (! empty($account['isDefault'])) {
                    $chosenId = trim((string) ($account['id'] ?? ''));
                    break;
                }
            }
        }

        foreach ($accounts as &$account) {
            $id = trim((string) ($account['id'] ?? ''));
            $account['isDefault'] = ($chosenId !== null && $chosenId !== '' && $id === $chosenId);
        }
        unset($account);

        return $accounts;
    }

    /**
     * @param  list<array<string, mixed>>  $accounts
     * @return list<array<string, mixed>>
     */
    private function sortBankDetailsDefaultFirst(array $accounts): array
    {
        usort($accounts, static function (array $a, array $b): int {
            $da = ! empty($a['isDefault']);
            $db = ! empty($b['isDefault']);
            if ($da === $db) {
                return 0;
            }

            return $da ? -1 : 1;
        });

        return $accounts;
    }

    /**
     * @param  array<string, mixed>  $account
     * @return array<string, mixed>
     */
    private function bankDetailPayloadFromAccount(array $account): array
    {
        return $this->filterBankDetailPayload([
            'type'              => $account['type'] ?? null,
            'accountNumber'     => $account['accountNumber'] ?? null,
            'accountType'       => $account['accountType'] ?? null,
            'accountName'       => $account['accountName'] ?? null,
            'accountBankCode'   => $account['accountBankCode'] ?? null,
            'accountAlias'      => $account['accountAlias'] ?? null,
            'networkIdentifier' => $account['networkIdentifier'] ?? null,
            'bankStreet'        => $account['bankStreet'] ?? null,
            'bankCity'          => $account['bankCity'] ?? null,
            'bankState'         => $account['bankState'] ?? null,
            'bankCountry'       => $account['bankCountry'] ?? null,
            'bankPostalCode'    => $account['bankPostalCode'] ?? null,
            'routingNumber'     => $account['routingNumber'] ?? null,
            'iban'              => $account['iban'] ?? null,
            'bic'               => $account['bic'] ?? null,
            'countryCode'       => $account['countryCode'] ?? null,
            'isDefault'         => (bool) ($account['isDefault'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function filterBankDetailPayload(array $payload): array
    {
        $keys = [
            'type', 'accountNumber', 'accountType', 'accountName', 'accountBankCode',
            'accountAlias', 'networkIdentifier', 'bankStreet', 'bankCity', 'bankState',
            'bankCountry', 'bankPostalCode', 'routingNumber', 'iban', 'bic', 'countryCode', 'isDefault',
        ];

        $out = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }
            $val = $payload[$key];
            if ($val === null) {
                continue;
            }
            if ($key === 'isDefault') {
                $out[$key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            } else {
                $out[$key] = is_bool($val) ? $val : (string) $val;
            }
        }

        return $out;
    }

    private function normalizePhone(string $input): string
    {
        $raw = preg_replace('/[^0-9]/', '', $input);
        if ($raw === '') {
            return '';
        }

        return substr($raw, 0, 1) !== '1' ? '1' . $raw : $raw;
    }

    private function resolveAlfredCustomerIdByPhone(string $inputPhone): ?string
    {
        $phone = $this->normalizePhone($inputPhone);
        if ($phone === '') {
            return null;
        }

        $client = Client::where('phone', $phone)->with('alfredAccount')->first();
        $alfredAccount = $client?->alfredAccount;
        if (!$alfredAccount) {
            $alfredAccount = AlfredAccount::where('phone', $phone)->first();
        }

        if (!$alfredAccount || empty($alfredAccount->alfred_customer_id)) {
            return null;
        }

        return (string) $alfredAccount->alfred_customer_id;
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

        // 3. Crear quote (fiat indirect /quotes)
        $quote = $this->createTransferQuote($customer->customerId, (float) $data['amount']);

        // 4. Ejecutar Offramp
        $offramp = $this->createOfframp([
            'quoteId' => $quote->quote_id,
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
