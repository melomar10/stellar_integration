<?php

namespace App\Http\Controllers;

use App\Models\AlfredOrder;
use App\Models\AlfredAccount;
use App\Models\Client;
use App\Services\AlfredService;
use App\Services\FlowService;
use App\Services\WasapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class AlfredController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/customer",
 *     summary="Crear un nuevo customer",
 *     operationId="createCustomer",
 *     tags={"Customer"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos necesarios para registrar un nuevo customer",
 *         @OA\JsonContent(
 *             required={"email", "phoneNumber"},
 *             @OA\Property(property="email", type="string", format="email", example="usuario@dominio.com"),
 *             @OA\Property(property="phoneNumber", type="string", example="+18095551234")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Customer creado exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="customerId", type="string", example="cus_123456"),
 *             @OA\Property(property="email", type="string", example="usuario@dominio.com"),
 *             @OA\Property(property="phoneNumber", type="string", example="+18095551234")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Los datos enviados no son válidos"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Error al Crear el customer"),
 *             @OA\Property(property="error", type="string", example="Detalle del error")
 *         )
 *     )
 * )
 */

    public function createCustomer(Request $req, AlfredService $alfred)
    {
        try {
            // Validación comentada por solicitud (evitar bloquear el flujo por ahora)
            // $data = $req->validate([
            //     'clientData' => 'required',
            //     'clientPhone' => 'required|string',
            //     'move_type' => 'required|string|in:Enviar,Recibir',
            // ]);
            $data = $req->all();

            $flowService = new FlowService();
            $flowJson = $flowService->convertFlowToJson($data['clientData'] ?? null);
            Log::debug('flowJson', [$flowJson]);
            if (!$flowJson) {
                return response()->json([
                    'ok' => false,
                    'message' => 'clientData inválido: no se pudo convertir a JSON',
                ], 422);
            }

            // Normalizar teléfono (misma lógica que ClientController)
            $inputPhone = $data['clientPhone'] ?? ($flowJson['phoneNumber'] ?? ($flowJson['phone'] ?? null));
            if (!$inputPhone) {
                return response()->json([
                    'ok' => false,
                    'message' => 'clientPhone es requerido',
                ], 422);
            }

            $phone = preg_replace('/[^0-9]/', '', (string) $inputPhone);
            if (substr($phone, 0, 1) !== '1') {
                $phone = '1' . $phone;
            }
            $phone = preg_replace('/[^0-9]/', '', $phone);

            $client = Client::where('phone', $phone)->first();
            if (!$client) {
                $client = Client::create([
                    'name' => $flowJson['firstName'] ?? ($flowJson['name'] ?? 'Sin nombre'),
                    'last_name' => $flowJson['lastName'] ?? ($flowJson['last_name'] ?? null),
                    'email' => $flowJson['email'] ?? null,
                    'phone' => $phone,
                    'uuid' => Uuid::uuid4()->toString(),
                    'status' => true,
                    'has_account' => false,
                    'country' => $flowJson['country'] ?? 'DO',
                ]);
            } else {
                // Si ya existe localmente, sincroniza los datos base del flow
                $client->name = $flowJson['firstName'] ?? $client->name;
                $client->last_name = $flowJson['lastName'] ?? $client->last_name;
                $client->email = $flowJson['email'] ?? $client->email;
                $client->country = $flowJson['country'] ?? $client->country;
                $client->save();
            }
            Log::debug('client created', [$client]);

            // 1) Crear customer en Alfred primero.
            // Si responde 409, buscamos el customer por phone y nos quedamos con items[0].
            $createResp = $alfred->createCustomerRaw([
                'firstName' => $flowJson['firstName'] ?? null,
                'lastName' => $flowJson['lastName'] ?? null,
                'email' => $flowJson['email'] ?? null,
                'phone' => $flowJson['phoneNumber'] ?? ($flowJson['phone'] ?? $inputPhone),
                'address' => $flowJson['address'] ?? ($flowJson['streetAddress'] ?? null),
                'city' => $flowJson['city'] ?? null,
                'country' => $flowJson['country'] ?? null,
                'postalCode' => $flowJson['postalCode'] ?? null,
                'dateOfBirth' => $flowJson['dateOfBirth'] ?? null,
                'isActive' => true,
            ]);

            if (!empty($createResp['_conflict'])) {
                $search = $alfred->searchCustomers([
                    'phone' => $flowJson['phoneNumber'] ?? ($flowJson['phone'] ?? $inputPhone),
                    'isActive' => 'true',
                ]);
                $items = is_array($search) ? ($search['items'] ?? []) : [];
                if (empty($items[0]) || !is_array($items[0])) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'El customer ya existe (409) pero no se pudo recuperar con la búsqueda por phone.',
                        'alfred' => $search,
                    ], 409);
                }
                $customer = $items[0];
            } else {
                $customer = $createResp;
            }

            $customerId = $customer['id'] ?? null;
            if (!$customerId) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Respuesta inválida de Alfred: falta id del customer',
                    'alfred' => $customer,
                ], 500);
            }

            // move_type -> on/off ramp + role
            $moveType = $data['move_type'] ?? 'Enviar';
            if ($moveType === 'Enviar') {
                $onRampCountry = 'US';
                $offRampCountry = 'DO';
                $onRampCurrency = 'USD';
                $offRampCurrency = 'DOP';
                $role = 'sender';
            } else {
                $onRampCountry = 'DO';
                $offRampCountry = 'US';
                $onRampCurrency = 'DOP';
                $offRampCurrency = 'USD';
                $role = 'receiver';
            }

            // Guardar/actualizar AlfredAccount local
            $alfredAccount = AlfredAccount::updateOrCreate(
                ['alfred_customer_id' => (string) $customerId],
                [
                    'first_name' => $flowJson['firstName'] ?? null,
                    'middle_name' => $flowJson['middleName'] ?? null,
                    'last_name' => $flowJson['lastName'] ?? null,
                    'full_name' => $customer['fullName'] ?? (trim(($flowJson['firstName'] ?? '') . ' ' . ($flowJson['lastName'] ?? '')) ?: null),
                    'email' => $flowJson['email'] ?? null,
                    'phone' => $customer['phone'] ?? ($flowJson['phoneNumber'] ?? ($flowJson['phone'] ?? null)),
                    'address' => $customer['address'] ?? ($flowJson['address'] ?? null),
                    'residential_address' => $flowJson['residentialAddress'] ?? null,
                    'street_address' => $flowJson['streetAddress'] ?? null,
                    'street_address_line2' => $flowJson['streetAddressLine2'] ?? null,
                    'city' => $customer['city'] ?? ($flowJson['city'] ?? null),
                    'state_province_region' => $flowJson['stateProvinceRegion'] ?? null,
                    'country' => $customer['country'] ?? ($flowJson['country'] ?? null),
                    'postal_code' => $customer['postalCode'] ?? ($flowJson['postalCode'] ?? null),
                    'preferred_language' => $flowJson['preferredLanguage'] ?? null,
                    'is_pep' => array_key_exists('isPep', $flowJson) ? filter_var($flowJson['isPep'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
                    'dni' => $flowJson['dni'] ?? null,
                    'national_id' => $flowJson['nationalId'] ?? null,
                    'cpf' => $flowJson['cpf'] ?? null,
                    'license_id' => $flowJson['licenseId'] ?? null,
                    'gender' => $customer['gender'] ?? ($flowJson['gender'] ?? null),
                    'date_of_birth' => $customer['dateOfBirth'] ?? ($flowJson['dateOfBirth'] ?? null),
                    'place_of_birth' => $flowJson['placeOfBirth'] ?? null,
                    'main_nationality' => $flowJson['mainNationality'] ?? null,
                    'secondary_nationalities' => $flowJson['secondaryNationalities'] ?? null,
                    'move_type' => $moveType,
                    'role' => $role,
                    'on_ramp_country' => $onRampCountry,
                    'off_ramp_country' => $offRampCountry,
                    'on_ramp_currency' => $onRampCurrency,
                    'off_ramp_currency' => $offRampCurrency,
                    'is_active' => (bool) ($customer['isActive'] ?? true),
                    'extras' => $flowJson['extras'] ?? null,
                    'file_types' => $flowJson['fileTypes'] ?? null,
                    'files' => $flowJson['files'] ?? null,
                    'links' => $customer['_links'] ?? null,
                    'alfred_created_at' => $customer['createdAt'] ?? null,
                    'alfred_updated_at' => $customer['updatedAt'] ?? null,
                ]
            );
            Log::debug('alfredAccount', [$alfredAccount]);
            $client->alfred_account_id = $alfredAccount->id;
            $client->save();
            Log::debug('client saved', [$client]);
            // Ejecutar KYC (multipart)
            $kycResponse = $alfred->KYC((string) $customerId, [
                'firstName' => $flowJson['firstName'] ?? null,
                'middleName' => $flowJson['middleName'] ?? null,
                'lastName' => $flowJson['lastName'] ?? null,
                'gender' => $flowJson['gender'] ?? null,
                'dateOfBirth' => $flowJson['dateOfBirth'] ?? null,
                'placeOfBirth' => $flowJson['placeOfBirth'] ?? null,
                'mainNationality' => $flowJson['mainNationality'] ?? null,
                'secondaryNationalities' => $flowJson['secondaryNationalities'] ?? null,
                'streetAddress' => $flowJson['streetAddress'] ?? null,
                'streetAddressLine2' => $flowJson['streetAddressLine2'] ?? null,
                'residentialAddress' => $flowJson['residentialAddress'] ?? ($flowJson['streetAddress'] ?? null),
                'city' => $flowJson['city'] ?? null,
                'stateProvinceRegion' => $flowJson['stateProvinceRegion'] ?? null,
                'postalCode' => $flowJson['postalCode'] ?? null,
                'country' => $flowJson['country'] ?? null,
                'email' => $flowJson['email'] ?? null,
                'phoneNumber' => $flowJson['phoneNumber'] ?? null,
                'preferredLanguage' => $flowJson['preferredLanguage'] ?? null,
                'isPep' => $flowJson['isPep'] ?? null,
                'dni' => $flowJson['dni'] ?? null,
                'nationalId' => $flowJson['nationalId'] ?? null,
                'cpf' => $flowJson['cpf'] ?? null,
                'licenseId' => $flowJson['licenseId'] ?? null,
                'onRampCountry'  => $onRampCountry,
                'offRampCountry' => $offRampCountry,
                'onRampCurrency' => $onRampCurrency,
                'offRampCurrency'=> $offRampCurrency,
                'role'           => $role,
                'fileTypes'      => $flowJson['fileTypes'] ?? null,
            ]);
            Log::debug('kycResponse', [$kycResponse]);

            $kycId = null;
            if (is_array($kycResponse)) {
                $kycId = $kycResponse['kycId']
                    ?? $kycResponse['id']
                    ?? ($kycResponse['data']['kycId'] ?? null)
                    ?? ($kycResponse['data']['id'] ?? null)
                    ?? ($kycResponse['submissionId'] ?? null);
            }

            if (!empty($kycId)) {
                $alfredAccount->kyc_id = (string) $kycId;
                $alfredAccount->save();

                try {
                    $alfred->updateCustomer((string) $customerId, ['kycId' => (string) $kycId]);
                } catch (\Throwable $e) {
                    Log::warning('No se pudo actualizar customer con kycId', [
                        'customerId' => $customerId,
                        'kycId' => $kycId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'ok' => true,
                'client' => $client->fresh()->load('alfredAccount'),
                'alfred_account' => $alfredAccount,
                'alfred_customer_id' => $customerId,
                'kyc_id' => $kycId,
                'kyc' => $kycResponse,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error createCustomer Alfred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Error al crear customer en Alfred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Nuevo endpoint solicitado: createClienAlfred (wrapper del flujo anterior)
    public function createClienAlfred(Request $req, AlfredService $alfred)
    {
        return $this->createCustomer($req, $alfred);
    }

    public function kycRequirements(Request $req, AlfredService $alfred)
    {
        $country = $req->query('country', 'DO');
        return response()->json($alfred->getKycRequirements($country));
    }

    /**
     * Busca el customer de Alfred por teléfono local y consulta GET /kyc/customer/{id}/status.
     *
     * Solo se envía `phone` en el body. Los parámetros de rampa son fijos: US/DO, USD/DOP, role sender.
     *
     * @OA\Post(
     *     path="/api/alfred/kyc/status-by-phone",
     *     summary="Consultar estado KYC por teléfono del cliente",
     *     description="Normaliza el teléfono, resuelve el customer ID en Alfred y consulta el KYC usando siempre onRamp US/USD, offRamp DO/DOP y role sender.",
     *     operationId="alfredKycStatusByPhone",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="8095551234", description="Teléfono del cliente (se normaliza a formato con prefijo 1)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consulta exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="phone", type="string", example="18095551234"),
     *             @OA\Property(property="customer_id", type="string", format="uuid", example="597751fd-eaae-477f-9231-1cb147f179f8"),
     *             @OA\Property(property="kyc_approved", type="boolean", example=true, description="true si status del KYC es approved"),
     *             @OA\Property(property="kyc_status", type="string", example="approved", nullable=true),
     *             @OA\Property(
     *                 property="kyc",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="approved"),
     *                 @OA\Property(property="fullName", type="string", example="Omar Martinez"),
     *                 @OA\Property(property="statusVerifiedAt", type="string", format="date-time"),
     *                 @OA\Property(property="createdAt", type="string", format="date-time"),
     *                 @OA\Property(property="updatedAt", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado o sin customer ID en Alfred",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=502,
     *         description="Error al consultar el API de Alfred",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function kycStatusByPhone(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone' => 'required|string|min:7',
            ]);

            $raw   = preg_replace('/[^0-9]/', '', (string) $data['phone']);
            $phone = substr($raw, 0, 1) !== '1' ? '1' . $raw : $raw;

            $client = Client::where('phone', $phone)->with('alfredAccount')->first();
            $alfredAccount = $client?->alfredAccount;
            if (!$alfredAccount) {
                $alfredAccount = AlfredAccount::where('phone', $phone)->first();
            }

            if (!$alfredAccount || empty($alfredAccount->alfred_customer_id)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se encontró un cliente con ese teléfono o aún no tiene cuenta vinculada en Alfred (falta customer ID).',
                    'phone'   => $phone,
                ], 404);
            }

            $customerId = (string) $alfredAccount->alfred_customer_id;

            $params = [
                'onRampCountry'   => 'US',
                'offRampCountry'  => 'DO',
                'onRampCurrency'  => 'USD',
                'offRampCurrency' => 'DOP',
                'role'            => 'sender',
            ];

            Log::debug('Alfred kycStatusByPhone', ['phone' => $phone, 'customerId' => $customerId, 'params' => $params]);

            $kyc = $alfred->getKycCustomerStatus($customerId, $params);

            $status = is_array($kyc) ? ($kyc['status'] ?? null) : null;
            $approved = is_string($status) && strcasecmp($status, 'approved') === 0;

            return response()->json([
                'ok'           => true,
                'phone'        => $phone,
                'customer_id'  => $customerId,
                'kyc_approved' => $approved,
                'kyc_status'   => $status,
                'kyc'          => $kyc,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Alfred kycStatusByPhone', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo consultar el estado KYC en Alfred.',
                'error'   => $e->getMessage(),
            ], 502);
        }
    }

    public function addKycInfo(Request $req, AlfredService $alfred, $id)
    {
        $kyc = $req->validate([ /* reglas según requirements */]);
        return response()->json($alfred->addKycInfo($id, $kyc));
    }

    public function submitKyc(Request $req, AlfredService $alfred, $id, $sub)
    {
        return response()->json($alfred->submitKyc($id, $sub));
    }
    /**
     * @OA\Post(
     *     path="/api/alfred/quotes",
     *     summary="Crear cotización por customerId (quote en estado pending)",
     *     operationId="alfredCreateQuote",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customerId"},
     *             @OA\Property(property="customerId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440004"),
     *             @OA\Property(property="amount", type="number", format="float", example=100),
     *             @OA\Property(property="fromAmount", type="number", format="float", example=100, description="Alias de amount")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quote creada",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="quote", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function createQuote(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'customerId' => 'required|string|max:64',
                'fromAmount' => 'required_without:amount|nullable|numeric|min:0.01|max:1000000',
                'amount'     => 'required_without:fromAmount|nullable|numeric|min:0.01|max:1000000',
            ]);

            $amt = $data['fromAmount'] ?? $data['amount'];
            $quote = $alfred->createTransferQuote($data['customerId'], (float) $amt);

            return response()->json([
                'ok'    => true,
                'quote' => $quote,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la quote',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crea quote por teléfono: resuelve customerId, llama POST /quotes y guarda en estado pending.
     *
     * @OA\Post(
     *     path="/api/alfred/quotes/by-phone",
     *     summary="Crear cotización por teléfono (quote en estado pending)",
     *     operationId="alfredCreateQuoteByPhone",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "amount"},
     *             @OA\Property(property="phone", type="string", example="8095551234"),
     *             @OA\Property(property="amount", type="number", format="float", example=100, description="Monto en USD a cotizar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quote creada y guardada como pending",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(
     *                 property="quote",
     *                 type="object",
     *                 @OA\Property(property="quote_id", type="string", format="uuid"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="from_amount", type="number", example=100),
     *                 @OA\Property(property="rate", type="string"),
     *                 @OA\Property(property="to_amount", type="string"),
     *                 @OA\Property(property="expiration", type="integer"),
     *                 @OA\Property(property="on_ramp_external_quote_id", type="string"),
     *                 @OA\Property(property="off_ramp_external_quote_id", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cliente no encontrado"),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=502, description="Error al consultar Alfred")
     * )
     */
    public function createQuoteByPhone(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone'  => 'required|string|min:7',
                'amount' => 'required|numeric|min:0.01|max:1000000',
            ]);

            $resolved = $this->resolveAlfredCustomerByPhone((string) $data['phone']);
            if ($resolved === null) {
                $raw   = preg_replace('/[^0-9]/', '', (string) $data['phone']);
                $phone = substr($raw, 0, 1) !== '1' ? '1' . $raw : $raw;

                return response()->json([
                    'ok'      => false,
                    'message' => 'No se encontró un cliente con ese teléfono o no tiene customer ID en Alfred.',
                    'phone'   => $phone,
                ], 404);
            }

            $amt = (float) $data['amount'];
            $quote = $alfred->createTransferQuote($resolved['customer_id'], $amt);
            Log::debug('Alfred createQuoteByPhone', ['quote' => $quote]);
            return response()->json([
                'ok'          => true,
                'phone'       => $resolved['phone'],
                'customer_id' => $resolved['customer_id'],
                'quote'       => $quote,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Alfred createQuoteByPhone', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo crear la cotización en Alfred.',
                'error'   => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * @return array{phone: string, customer_id: string}|null
     */
    private function resolveAlfredCustomerByPhone(string $inputPhone): ?array
    {
        $raw   = preg_replace('/[^0-9]/', '', $inputPhone);
        $phone = substr($raw, 0, 1) !== '1' ? '1' . $raw : $raw;

        $client = Client::where('phone', $phone)->with('alfredAccount')->first();
        $alfredAccount = $client?->alfredAccount;
        if (!$alfredAccount) {
            $alfredAccount = AlfredAccount::where('phone', $phone)->first();
        }

        if (!$alfredAccount || empty($alfredAccount->alfred_customer_id)) {
            return null;
        }

        return [
            'phone'        => $phone,
            'customer_id'  => (string) $alfredAccount->alfred_customer_id,
        ];
    }

    /**
     * @OA\Post(
     *     path="/api/alfred/orders",
     *     summary="Crear orden por teléfonos del remitente y destinatario",
     *     description="Busca la última quote pending del sender y los customerId por teléfono.",
     *     operationId="alfredCreateOrder",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_sender", "phone_receiver"},
     *             @OA\Property(property="phone_sender", type="string", example="16466696038"),
     *             @OA\Property(property="phone_receiver", type="string", example="18095551234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden creada; quote marcada como completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="order_id", type="string", format="uuid"),
     *             @OA\Property(property="quote_id", type="string", format="uuid"),
     *             @OA\Property(property="total_amount", type="string", example="10.00"),
     *             @OA\Property(property="currency", type="string", example="USD")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=404, description="Cliente, quote u orden no encontrada"),
     *     @OA\Response(response=502, description="Error Alfred")
     * )
     */
    public function createOrder(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone_sender'   => 'required|string|min:7',
                'phone_receiver' => 'required|string|min:7',
            ]);

            $result = $alfred->createOrderByPhones(
                $data['phone_sender'],
                $data['phone_receiver']
            );

            return response()->json([
                'ok'           => true,
                'order_id'     => $result['order_id'],
                'quote_id'     => $result['quote_id'],
                'total_amount' => $result['total_amount'],
                'currency'     => $result['currency'],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Alfred createOrder', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo crear la orden en Alfred.',
                'error'   => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/alfred/orders/confirm-by-phone",
     *     summary="Confirmar orden pendiente por teléfono del remitente",
     *     description="Busca la última orden con api_status pending del sender y devuelve paymentInstructions (ACH).",
     *     operationId="alfredConfirmOrderByPhone",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="16466696038")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Instrucciones de pago del sender",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="order_id", type="string", format="uuid"),
     *             @OA\Property(property="payment_method", type="string", example="ach_push"),
     *             @OA\Property(
     *                 property="payment_instructions",
     *                 type="object",
     *                 @OA\Property(property="amount", type="string", example="11.00"),
     *                 @OA\Property(property="bankName", type="string"),
     *                 @OA\Property(property="currency", type="string"),
     *                 @OA\Property(property="bankAccount", type="string"),
     *                 @OA\Property(property="depositMessage", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Sin orden pendiente o sin customer"),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=502, description="Error Alfred")
     * )
     */
    public function confirmOrderByPhone(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone' => 'required|string|min:7',
            ]);

            $result = $alfred->confirmOrderByPhone($data['phone']);

            return response()->json($result['payment_instructions'] , 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Alfred confirmOrderByPhone', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudieron obtener las instrucciones de la orden.',
                'error'   => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/alfred/orders/complete",
     *     summary="Completar orden (callback Alfred)",
     *     description="Alfred notifica el resultado de la transacción. Actualiza status por order_id (completed por defecto; luego refunded, reversed, etc.).",
     *     operationId="alfredCompleteOrder",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="string", format="uuid", example="b586dddf-c6c8-487a-9371-3c2d0c0b432a"),
     *             @OA\Property(property="status", type="string", example="completed", description="Estado final: completed (default), refunded, reversed, failed, etc.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden marcada como completada",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="order_id", type="string", format="uuid"),
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="already_completed", type="boolean", example=false),
     *             @OA\Property(property="order", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Orden no encontrada"),
     *     @OA\Response(response=422, description="Validación"),
     *     @OA\Response(response=500, description="Error interno")
     * )
     */
    public function completeOrder(Request $req, AlfredService $alfred, WasapiService $wasapi)
    {
        try {
            $data = $req->validate([
                'order_id' => 'required_without:orderId|string|max:64',
                'orderId'  => 'required_without:order_id|string|max:64',
                'status'   => 'nullable|string|max:64',
            ]);

            $orderId = trim((string) ($data['order_id'] ?? $data['orderId'] ?? ''));
            $status = isset($data['status']) ? trim((string) $data['status']) : null;

            $result = $alfred->completeOrder($orderId, $status !== '' ? $status : null);

            $whatsapp = null;
            if (
                ! $result['already_completed']
                && $result['status'] === AlfredOrder::STATUS_COMPLETED
                && $result['order'] instanceof AlfredOrder
            ) {
                $order = $result['order'];
                $senderPhone = $alfred->resolvePhoneByAlfredCustomerId((string) $order->sender_customer_id);
                $receiverPhone = $alfred->resolvePhoneByAlfredCustomerId((string) $order->receiver_customer_id);
                $whatsapp = $wasapi->notifyOrderTransactionComplete($senderPhone, $receiverPhone);
            }

            return response()->json([
                'ok'                => true,
                'order_id'          => $result['order_id'],
                'status'            => $result['status'],
                'already_completed' => $result['already_completed'],
                'order'             => $result['order'],
                'whatsapp'          => $whatsapp,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Alfred completeOrder', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo completar la orden.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function createOnramp(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([ /* quote_id, amount, etc. */]);
        return response()->json($alfred->createOnramp($data));
    }

    /**
 * @OA\Post(
 *     path="/api/payment-method",
 *     summary="Crear un nuevo método de pago",
 *     operationId="createPaymentMethod",
 *     tags={"Payment Methods"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos necesarios para registrar un nuevo método de pago",
 *         @OA\JsonContent(
 *             required={"type", "fiatAccountFields", "customerId"},
 *             @OA\Property(property="type", type="string", enum={"PIX", "SPEI", "COELSA"}, example="PIX"),
 *             @OA\Property(
 *                 property="fiatAccountFields",
 *                 type="object",
 *                 required={"accountNumber", "accountType"},
 *                 @OA\Property(property="accountNumber", type="string", example="1234567890"),
 *                 @OA\Property(property="accountType", type="string", enum={"cpf", "cnpj"}, example="cpf")
 *             ),
 *             @OA\Property(property="customerId", type="string", example="cus_xyz123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Método de pago creado correctamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="string", example="method_12345"),
 *             @OA\Property(property="status", type="string", example="active"),
 *             @OA\Property(property="provider", type="string", example="Banco do Brasil")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Los datos enviados no son válidos"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Error al Crear métodos de pago"),
 *             @OA\Property(property="error", type="string", example="Excepción lanzada")
 *         )
 *     )
 * )
 */
    public function createPaymentMethod(Request $req, AlfredService $alfred)
    {
       try {

            $req->merge([
                'type' => $req->input('type', 'PIX'),
                'fiatAccountFields.accountType' => $req->input('fiatAccountFields.accountType', 'cpf'),
            ]);

            $data = $req->validate([
                'type'                     => 'required|string|in:PIX,SPEI,COELSA', // limita a opciones conocidas
                'fiatAccountFields'        => 'required|array',
                'fiatAccountFields.accountNumber' => 'required|string|max:50',
                'fiatAccountFields.accountType'   => 'required|string|in:cpf,cnpj', // según los tipos válidos en tu sistema
                'customerId'              => 'required|string|max:300',
            ]);

            $dto = $alfred->createPaymentMethod($data);
            return response()->json((array) $dto, 200);

          } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al Crear métodos de pago',
                    'error' => $e->getMessage(),
                ], 500);
            }
    }
/**
 * @OA\Get(
 *     path="/api/payment-methods",
 *     summary="Obtener métodos de pago del cliente",
 *     operationId="getPaymentMethods",
 *     tags={"Payment Methods"},
 *     @OA\Parameter(
 *         name="customerId",
 *         in="query",
 *         required=true,
 *         description="ID del cliente registrado en Alfred",
 *         @OA\Schema(type="string", example="cus_abc123")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lista de métodos de pago obtenida exitosamente",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="string", example="method_123"),
 *                 @OA\Property(property="type", type="string", example="bank_account"),
 *                 @OA\Property(property="provider", type="string", example="Banco Popular"),
 *                 @OA\Property(property="accountNumber", type="string", example="********1234"),
 *                 @OA\Property(property="currency", type="string", example="DOP")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Parámetro customerId ausente o inválido",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="customerId es requerido"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Error al obtener métodos de pago"),
 *             @OA\Property(property="error", type="string", example="Mensaje de excepción")
 *         )
 *     )
 * )
 */
    public function getPaymentMethods(Request $req, AlfredService $alfred)
    {
          try {
                $dto = $alfred->getPaymentMethods($req->query('customerId'));

                return response()->json((array) $dto, 200);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al obtener métodos de pago',
                    'error' => $e->getMessage(),
                ], 500);
            }
    }
    /**
 * @OA\Post(
 *     path="/api/offramp",
 *     summary="Crear operación de Offramp",
 *     operationId="createOfframp",
 *     tags={"Offramp"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"customerId", "quoteId", "amount", "fiatAccountId"},
 *             @OA\Property(property="fromCurrency", type="string", example="USD", description="Criptomoneda de origen (3 letras)"),
 *             @OA\Property(property="toCurrency", type="string", example="DO", description="Moneda fiat destino (3 letras)"),
 *             @OA\Property(property="chain", type="string", example="XLM", description="Cadena de blockchain"),
 *             @OA\Property(property="customerId", type="string", example="cus_abc123", maxLength=100),
 *             @OA\Property(property="quoteId", type="string", example="quote_xyz456", maxLength=100),
 *             @OA\Property(property="amount", type="number", format="float", example=150.50, minimum=10, maximum=1000),
 *             @OA\Property(property="fiatAccountId", type="string", example="acc_789xyz", maxLength=100)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Operación de Offramp creada exitosamente",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="offrampId", type="string", example="offramp_123abc")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Los datos enviados no son válidos"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Error interno"),
 *             @OA\Property(property="error", type="string", example="Mensaje de excepción")
 *         )
 *     )
 * )
 */
    public function createOfframp(Request $req, AlfredService $alfred)
    {
        $req->merge([
            'fromCurrency' => $req->input('fromCurrency', 'USD'),
            'toCurrency'   => $req->input('toCurrency', 'DO'),
            'chain'        => $req->input('chain', 'DO'),
        ]);

        $data = $req->validate([
            'fromCurrency'   => 'required|string|size:3',     // Ej: USDT
            'toCurrency'     => 'required|string|size:3',     // Ej: MEX
            'chain'          => 'required|string|max:10',     // Ej: XLM
            'customerId'     => 'required|string|max:100',
            'quoteId'        => 'required|string|max:100',
            'amount'         => 'required|numeric|min:10|max:1000',
            'fiatAccountId'  => 'required|string|max:100',
        ]);

        return response()->json($alfred->createOfframp($data));
    }

    public function showKycForm()
    {
        return view('alfred.kyc-form');
    }

    public function checkPhone(Request $req)
    {
        try {
            $req->validate(['phone' => 'required|string|min:7']);

            $raw   = preg_replace('/[^0-9]/', '', (string) $req->phone);
            $phone = substr($raw, 0, 1) !== '1' ? '1' . $raw : $raw;

            Log::debug('Alfred checkPhone', ['raw' => $req->phone, 'normalized' => $phone]);

            $client = Client::where('phone', $phone)->with('alfredAccount')->first();

            if (!$client) {
                $alfredAccount = AlfredAccount::where('phone', $phone)->first();
                if ($alfredAccount) {
                    $client = $alfredAccount->client;
                }
            }

            if (!$client || !$client->alfred_account_id) {
                Log::debug('Alfred checkPhone: client not found', ['phone' => $phone]);
                return response()->json(['found' => false, 'phone' => $phone]);
            }

            $alfredAccount = $client->alfredAccount;
            Log::debug('Alfred checkPhone: client found', ['client_id' => $client->id, 'customer_id' => $alfredAccount->alfred_customer_id ?? null]);

            return response()->json([
                'found'          => true,
                'phone'          => $phone,
                'client'         => $client->only(['id', 'name', 'last_name', 'email', 'phone', 'country']),
                'alfred_account' => $alfredAccount,
                'customer_id'    => $alfredAccount->alfred_customer_id ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Alfred checkPhone error', ['message' => $e->getMessage()]);
            return response()->json(['found' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function createClient(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'firstName' => 'required|string|max:100',
                'lastName'  => 'required|string|max:100',
                'email'     => 'required|email|max:150',
                'phone'     => 'required|string|max:30',
            ]);

            $phone = preg_replace('/[^0-9]/', '', (string) $data['phone']);
            if (substr($phone, 0, 1) !== '1') $phone = '1' . $phone;

            Log::debug('Alfred createClient input', ['phone' => $phone, 'email' => $data['email']]);

            $client = Client::where('phone', $phone)->first();
            if (!$client) {
                $client = Client::create([
                    'name'       => $data['firstName'],
                    'last_name'  => $data['lastName'],
                    'email'      => $data['email'],
                    'phone'      => $phone,
                    'uuid'       => Uuid::uuid4()->toString(),
                    'status'     => true,
                    'has_account'=> false,
                    'country'    => 'DO',
                ]);
            } else {
                $client->update(['name' => $data['firstName'], 'last_name' => $data['lastName'], 'email' => $data['email']]);
            }
            Log::debug('Alfred createClient local client', [$client->toArray()]);

            $search = $alfred->searchCustomers(['phone' => $phone, 'isActive' => 'true']);
            Log::debug('Alfred createClient searchCustomers response', [$search]);
            $existingItems = is_array($search) ? ($search['items'] ?? []) : [];

            if (!empty($existingItems[0]) && is_array($existingItems[0])) {
                $customer = $existingItems[0];
                Log::debug('Alfred createClient: reusing existing Alfred customer', ['id' => $customer['id'] ?? null]);
            } else {
                $createResp = $alfred->createCustomerRaw([
                    'firstName' => $data['firstName'],
                    'lastName'  => $data['lastName'],
                    'email'     => $data['email'],
                    'phone'     => $phone,
                    'isActive'  => true,
                ]);
                Log::debug('Alfred createClient createCustomerRaw response', [$createResp]);

                if (!empty($createResp['_conflict'])) {
                    $search2 = $alfred->searchCustomers(['phone' => $phone, 'isActive' => 'true']);
                    Log::debug('Alfred createClient conflict - retry search', [$search2]);
                    $items2 = is_array($search2) ? ($search2['items'] ?? []) : [];
                    if (empty($items2[0])) {
                        return response()->json(['success' => false, 'message' => 'Cliente ya existe en Alfred pero no se pudo recuperar.'], 409);
                    }
                    $customer = $items2[0];
                } else {
                    $customer = $createResp;
                }
            }

            $customerId = $customer['id'] ?? null;
            if (!$customerId) {
                return response()->json(['success' => false, 'message' => 'Alfred no retornó ID de cliente.'], 500);
            }

            $alfredAccount = AlfredAccount::updateOrCreate(
                ['alfred_customer_id' => (string) $customerId],
                [
                    'first_name' => $data['firstName'],
                    'last_name'  => $data['lastName'],
                    'email'      => $data['email'],
                    'phone'      => $phone,
                    'is_active'  => true,
                    'alfred_created_at' => $customer['createdAt'] ?? null,
                    'alfred_updated_at' => $customer['updatedAt'] ?? null,
                    'links'      => $customer['_links'] ?? null,
                ]
            );
            Log::debug('Alfred createClient alfredAccount', [$alfredAccount->toArray()]);

            $client->alfred_account_id = $alfredAccount->id;
            $client->save();

            return response()->json([
                'success'      => true,
                'client'       => $client->only(['id', 'name', 'last_name', 'email', 'phone', 'country']),
                'alfred_account' => $alfredAccount,
                'customer_id'  => $customerId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Alfred createClient error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getKycStatus(Request $req, AlfredService $alfred)
    {
        try {
            $req->validate([
                'customer_id' => 'required|string',
                'move_type'   => 'required|string|in:Enviar,Recibir',
            ]);

            $customerId = $req->customer_id;
            $moveType   = $req->move_type;

            $params = [
                'onRampCountry'   => 'US',
                'offRampCountry'  => 'DO',
                'onRampCurrency'  => 'USD',
                'offRampCurrency' => 'DOP',
                'role'            => $moveType === 'Enviar' ? 'sender' : 'receiver',
            ];

            Log::debug('Alfred getKycStatus request', array_merge(['customerId' => $customerId], $params));
            $kycStatus = $alfred->getKycCustomerStatus($customerId, $params);
            Log::debug('Alfred getKycStatus response', [$kycStatus]);

            return response()->json(['success' => true, 'kyc_status' => $kycStatus, 'params' => $params]);
        } catch (\Throwable $e) {
            Log::error('Alfred getKycStatus error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function submitKycForm(Request $req, AlfredService $alfred, WasapiService $wasapi)
    {
        try {
            $req->validate([
                'customer_id' => 'required|string',
                'move_type'   => 'required|string|in:Enviar,Recibir',
            ]);

            $customerId = $req->customer_id;
            $moveType   = $req->move_type;

            $onRampCountry   = 'US';
            $offRampCountry  = 'DO';
            $onRampCurrency  = 'USD';
            $offRampCurrency = 'DOP';
            $role            = $moveType === 'Enviar' ? 'sender' : 'receiver';

            // Mapeo campo del form → valor válido en fileTypes de Alfred
            $fileTypeMap = [
                'idFront'            => 'id_front',
                'idBack'             => 'id_back',
                'driverLicenseFront' => 'driver_license_front',
                'driverLicenseBack'  => 'driver_license_back',
                'selfie'             => 'selfie',
                'proofOfAddress'     => 'proof_of_address',
                'bankStatement'      => 'bank_statement',
                'incomeProof'        => 'income_proof',
            ];

            $filePaths = [];   // array indexado de rutas absolutas
            $fileTypes = [];   // array de tipos válidos para Alfred

            foreach ($fileTypeMap as $inputField => $typeValue) {
                if ($req->hasFile($inputField) && $req->file($inputField)->isValid()) {
                    $stored = $req->file($inputField)->store('kyc_tmp', 'local');
                    $abs    = storage_path("app/{$stored}");
                    $filePaths[] = $abs;
                    $fileTypes[] = $typeValue;
                    Log::debug("Alfred submitKycForm stored file [{$inputField}→{$typeValue}]", ['path' => $abs]);
                }
            }

            // Recuperar alfredAccount antes del payload para usar datos existentes
            $alfredAccount = AlfredAccount::where('alfred_customer_id', (string) $customerId)->first();

            $textFields = [
                'firstName', 'middleName', 'lastName', 'email', 'phoneNumber',
                'gender', 'dateOfBirth', 'placeOfBirth', 'mainNationality', 'secondaryNationalities',
                'preferredLanguage', 'isPep', 'streetAddress', 'streetAddressLine2', 'residentialAddress',
                'city', 'stateProvinceRegion', 'country', 'postalCode',
                'nationalId', 'licenseId', 'dni', 'cpf',
            ];

            $kycPayload = [];
            foreach ($textFields as $field) {
                $val = $req->input($field);
                if (!is_null($val) && $val !== '') {
                    $kycPayload[$field] = $val;
                }
            }

            // ── Campos siempre obligatorios ──────────────────────────────────────
            // country: del form, o del alfredAccount guardado
            if (empty($kycPayload['country'])) {
                $kycPayload['country'] = $alfredAccount?->country ?? 'Dominican Republic';
            }

            // mainNationality: del form, del alfredAccount, o derivado del country
            if (empty($kycPayload['mainNationality'])) {
                $kycPayload['mainNationality'] = $alfredAccount?->main_nationality
                    ?? $this->countryToNationality($kycPayload['country']);
            }

            // Ramp params + role (siempre fijos)
            $kycPayload['onRampCountry']  = $onRampCountry;
            $kycPayload['offRampCountry'] = $offRampCountry;
            $kycPayload['onRampCurrency'] = $onRampCurrency;
            $kycPayload['offRampCurrency']= $offRampCurrency;
            $kycPayload['role']           = $role;
            $kycPayload['fileTypes']      = implode(',', $fileTypes);
            // ─────────────────────────────────────────────────────────────────────

            Log::debug('Alfred submitKycForm payload', $kycPayload);
            Log::debug('Alfred submitKycForm filePaths', $filePaths);

            $kycResponse = $alfred->KYC((string) $customerId, $kycPayload, $filePaths);

            Log::debug('Alfred submitKycForm kycResponse', [$kycResponse]);

            foreach ($filePaths as $path) {
                @unlink($path);
            }

            $kycId = $kycResponse['kycId'] ?? ($kycResponse['id'] ?? null);

            if ($alfredAccount) {
                $updateData = array_filter([
                    'first_name'            => $req->input('firstName'),
                    'middle_name'           => $req->input('middleName'),
                    'last_name'             => $req->input('lastName'),
                    'email'                 => $req->input('email'),
                    'gender'                => $req->input('gender'),
                    'date_of_birth'         => $req->input('dateOfBirth'),
                    'place_of_birth'        => $req->input('placeOfBirth'),
                    'main_nationality'      => $req->input('mainNationality'),
                    'secondary_nationalities'=> $req->input('secondaryNationalities'),
                    'preferred_language'    => $req->input('preferredLanguage'),
                    'is_pep'               => $req->input('isPep') === 'true' ? true : false,
                    'street_address'        => $req->input('streetAddress'),
                    'street_address_line2'  => $req->input('streetAddressLine2'),
                    'residential_address'   => $req->input('residentialAddress'),
                    'city'                  => $req->input('city'),
                    'state_province_region' => $req->input('stateProvinceRegion'),
                    'country'               => $req->input('country'),
                    'postal_code'           => $req->input('postalCode'),
                    'national_id'           => $req->input('nationalId'),
                    'license_id'            => $req->input('licenseId'),
                    'dni'                   => $req->input('dni'),
                    'cpf'                   => $req->input('cpf'),
                    'move_type'             => $moveType,
                    'role'                  => $role,
                    'on_ramp_country'       => $onRampCountry,
                    'off_ramp_country'      => $offRampCountry,
                    'on_ramp_currency'      => $onRampCurrency,
                    'off_ramp_currency'     => $offRampCurrency,
                    'file_types'            => implode(',', $fileTypes),
                    'kyc_id'               => $kycId,
                ], fn($v) => !is_null($v));

                $alfredAccount->update($updateData);
                Log::debug('Alfred submitKycForm alfredAccount updated', ['id' => $alfredAccount->id]);
            }

            $whatsappSent = $this->sendWasapiKycCompleteNotice($req, $wasapi, $customerId, $alfredAccount);

            return response()->json([
                'success'         => true,
                'kyc_id'          => $kycId,
                'customer_id'     => $customerId,
                'kyc'             => $kycResponse,
                'whatsapp_sent'   => $whatsappSent,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Alfred submitKycForm error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function sendWasapiKycCompleteNotice(Request $req, WasapiService $wasapi, string $customerId, ?AlfredAccount $alfredAccount): bool
    {
        $account = $alfredAccount;
        if (!$account) {
            $account = AlfredAccount::where('alfred_customer_id', $customerId)->first();
        } else {
            $account->refresh();
        }

        $phoneDigits = '';
        if ($account?->phone) {
            $phoneDigits = preg_replace('/\D/', '', (string) $account->phone) ?? '';
        }
        if ($phoneDigits === '') {
            $phoneDigits = preg_replace('/\D/', '', (string) $req->input('phoneNumber', '')) ?? '';
        }
        if ($phoneDigits === '' && $account) {
            $client = Client::where('alfred_account_id', $account->id)->first();
            if ($client?->phone) {
                $phoneDigits = preg_replace('/\D/', '', (string) $client->phone) ?? '';
            }
        }

        if ($phoneDigits === '') {
            Log::warning('Alfred KYC completado: sin teléfono para Wasapi', ['customer_id' => $customerId]);

            return false;
        }

        if (strlen($phoneDigits) >= 10 && ! str_starts_with($phoneDigits, '1')) {
            $phoneDigits = '1'.$phoneDigits;
        }

        $firstName = $req->input('firstName') ?: $account?->first_name;

        return $wasapi->notifyKycRegistrationComplete($phoneDigits, $firstName ? (string) $firstName : null);
    }

    private function countryToNationality(string $country): string
    {
        $map = [
            'Dominican Republic'   => 'DO',
            'Republica Dominicana' => 'DO',
            'República Dominicana' => 'DO',
            'United States'        => 'US',
            'USA'                  => 'US',
            'Mexico'               => 'MX',
            'México'               => 'MX',
            'Colombia'             => 'CO',
            'Brazil'               => 'BR',
            'Brasil'               => 'BR',
            'Argentina'            => 'AR',
            'Chile'                => 'CL',
            'Peru'                 => 'PE',
            'Perú'                 => 'PE',
        ];
        return $map[$country] ?? 'DO';
    }

    public function createSupport(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([ /* subject, message, etc. */]);
        return response()->json($alfred->createSupportTicket($data));
    }
/**
 * @OA\Post(
 *     path="/api/alfred/create-customer-country",
 *     summary="Crear un customer con país",
 *     tags={"Alfred"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "phoneNumber", "country"},
 *             @OA\Property(property="email", type="string", format="email", example="cliente@email.com"),
 *             @OA\Property(property="phoneNumber", type="string", example="8295550000"),
 *             @OA\Property(property="country", type="string", example="DO")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Customer creado",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="string"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="country", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al crear el customer country"
 *     )
 * )
 */
    public function createCustomerCountry(Request $req, AlfredService $alfred)
    {
        try{
            $data = $req->validate([
                'email'       => 'required|email',
                'phoneNumber' => 'required|string',
                'country'     => 'required|string',
            ]);

            $dto = $alfred->createCustomerCountry($data);

             return response()->json((array) $dto, 200);
        }catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al crear el customer country',
                    'error' => $e->getMessage(),
                ], 500);
            }

    }
    /**
 * @OA\Get(
 *     path="/api/customer",
 *     summary="Obtener cliente por email",
 *     operationId="getCustomerByEmail",
 *     tags={"Customer"},
 *     @OA\Parameter(
 *         name="email",
 *         in="query",
 *         required=true,
 *         description="Correo electrónico del cliente",
 *         @OA\Schema(type="string", format="email")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cliente encontrado exitosamente",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=123),
 *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
 *                 @OA\Property(property="email", type="string", example="juan@example.com")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cliente no encontrado",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Cliente no encontrado con el email proporcionado.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="El email es requerido y debe tener un formato válido"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error del servidor",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Error al obtener el customer"),
 *             @OA\Property(property="error", type="string", example="Exception message")
 *         )
 *     )
 * )
 */
    public function GetCustomerByEmail(Request $req, AlfredService $alfred)
    {
        try {
            // Validar el email
            $validator = Validator::make($req->query(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'El email es requerido y debe tener un formato válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $req->query('email');
            $dto = $alfred->GetCustomerByEmail($email);

            return response()->json((array) $dto, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el customer',
                'error' => $e->getMessage(),
            ], 500);
        }
   }
    /**
     * @OA\Post(
     *     path="/api/alfred/process-offramp",
     *     summary="Procesar Offramp completo (crea customer, método de pago, quote y ejecuta el flujo)",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phoneNumber", "accountNumber", "accountType", "amount"},
     *             @OA\Property(property="email", type="string", format="email", example="usuario@ejemplo.com"),
     *             @OA\Property(property="phoneNumber", type="string", example="8095551234"),
     *             @OA\Property(property="country", type="string", example="DO"),
     *             @OA\Property(property="accountNumber", type="string", example="123456789"),
     *             @OA\Property(property="accountType", type="string", example="AHORRO"),
     *             @OA\Property(property="amount", type="number", format="float", example=150),
     *             @OA\Property(property="fromCurrency", type="string", example="USD"),
     *             @OA\Property(property="toCurrency", type="string", example="DO"),
     *             @OA\Property(property="chain", type="string", example="DO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proceso Offramp exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="offramp", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error en el proceso Offramp",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error durante el flujo Offramp.")
     *         )
     *     )
     * )
     */

  public function processOfframp(Request $request, AlfredService $alfred)
    {
        $request->merge([
            'fromCurrency' => $request->input('fromCurrency', 'USD'),
            'toCurrency'   => $request->input('toCurrency', 'DO'),
            'chain'        => $request->input('chain', 'DO'),
        ]);

        $request->validate([
            'email' => 'nullable|email',
            'phoneNumber' => 'required',
            'country' => 'nullable|string',
            'accountNumber' => 'required',
            'accountType' => 'required',
            'amount' => 'required|numeric',
            'fromCurrency' => 'required|string',
            'toCurrency' => 'required|string'
        ]);

        try {
            // 1. Verificar o crear Customer
            try {
                $customer = $alfred->GetCustomerByEmail($request->email);
            } catch (\Throwable $e) {
                // Primero crea el customer básico
                $customer = $alfred->createCustomer([
                    'email' => $request->email,
                    'phoneNumber' => $request->phoneNumber,
                ]);

                // Luego, crea el customer con país si se proporcionó
                if ($request->filled('country')) {
                    $customer = $alfred->createCustomerCountry([
                        'email' => $request->email,
                        'phoneNumber' => $request->phoneNumber,
                        'country' => $request->country,
                    ]);
                }
            }

            // 2. Obtener o crear método de pago
            try {
                $paymentMethod = $alfred->getPaymentMethods($customer->customerId);
            } catch (\Throwable $e) {
                $paymentMethod = $alfred->createPaymentMethod([
                    'customerId' => $customer->customerId,
                    'type' => 'ACH_DOM',
                    'accountNumber' => $request->accountNumber,
                    'accountType' => $request->accountType,
                ]);
            }

            // 3. Crear Quote (POST /quotes fiat indirect)
            $quote = $alfred->createTransferQuote($customer->customerId, (float) $request->amount);

            // 4. Ejecutar Offramp
            $offramp = $alfred->createOfframp([
                'quoteId' => $quote->quote_id,
                'customerId' => $customer->customerId,
                'fiatAccountId' => $paymentMethod->fiatAccountId,
                'chain' => $request->chain,
                'fromCurrency' => $request->fromCurrency,
                'toCurrency' => $request->toCurrency,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'offramp' => $offramp
            ]);

        } catch (\Throwable $e) {
            Log::error('Error in Offramp flow', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Ocurrió un error durante el flujo Offramp.'], 500);
        }
    }

    public function showBankDetailsForm(string $phone)
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            abort(404, 'Teléfono inválido en la URL.');
        }

        return view('alfred.bank-details-form', [
            'phone' => $digits,
        ]);
    }

    public function showBankDetailsCreateForm(string $phone)
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            abort(404, 'Teléfono inválido en la URL.');
        }

        return view('alfred.bank-details-create', [
            'phone' => $digits,
        ]);
    }

    public function loadBankDetailsByPhone(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone' => 'required|string|min:7',
            ]);

            $result = $alfred->getBankDetailsByPhone($data['phone']);

            return response()->json([
                'success'     => true,
                'phone'       => $result['phone'],
                'customer_id' => $result['customer_id'],
                'accounts'    => $result['accounts'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            Log::error('Alfred loadBankDetailsByPhone', ['message' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function setDefaultBankDetail(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone'          => 'required|string|min:7',
                'bank_detail_id' => 'required|string|max:64',
            ]);

            $resolved = $this->resolveAlfredCustomerByPhone($data['phone']);
            if ($resolved === null) {
                return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
            }

            $accounts = $alfred->setDefaultCustomerBankDetail(
                $resolved['customer_id'],
                $data['bank_detail_id']
            );

            return response()->json([
                'success'     => true,
                'phone'       => $resolved['phone'],
                'customer_id' => $resolved['customer_id'],
                'accounts'    => $accounts,
                'message'     => 'Cuenta predeterminada actualizada.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            Log::error('Alfred setDefaultBankDetail', ['message' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function createBankDetail(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'phone'             => 'required|string|min:7',
                'type'              => 'required|string|max:32',
                'accountNumber'     => 'required|string|max:64',
                'accountType'       => 'required|string|max:32',
                'accountName'       => 'required|string|max:150',
                'accountBankCode'   => 'nullable|string|max:32',
                'accountAlias'      => 'nullable|string|max:64',
                'countryCode'       => 'nullable|string|max:8',
                'routingNumber'     => 'nullable|string|max:32',
                'isDefault'         => 'nullable|boolean',
            ]);

            $resolved = $this->resolveAlfredCustomerByPhone($data['phone']);
            if ($resolved === null) {
                return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
            }

            $phone = $data['phone'];
            unset($data['phone']);

            $created = $alfred->createCustomerBankDetail($resolved['customer_id'], $data);

            if (! empty($data['isDefault']) && ! empty($created['id'])) {
                $alfred->setDefaultCustomerBankDetail($resolved['customer_id'], (string) $created['id']);
            }

            $result = $alfred->getBankDetailsByPhone($phone);

            return response()->json([
                'success'     => true,
                'account'     => $created,
                'accounts'    => $result['accounts'],
                'customer_id' => $result['customer_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Alfred createBankDetail', ['message' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/alfred/customer/{customerId}/bank-details",
     *     summary="Listar cuentas bancarias del customer",
     *     operationId="alfredGetBankDetails",
     *     tags={"Alfred"},
     *     @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Lista de cuentas")
     * )
     */
    public function getBankDetails(Request $req, AlfredService $alfred, string $customerId)
    {
        try {
            $accounts = $alfred->getCustomerBankDetails($customerId);

            return response()->json(['ok' => true, 'accounts' => $accounts], 200);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/alfred/customer/{customerId}/bank-details",
     *     summary="Registrar cuenta bancaria",
     *     operationId="alfredCreateBankDetail",
     *     tags={"Alfred"},
     *     @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"type","accountNumber","accountType","accountName"},
     *         @OA\Property(property="type", type="string", example="ACH_DOM"),
     *         @OA\Property(property="accountNumber", type="string"),
     *         @OA\Property(property="accountType", type="string", example="CORRIENTE"),
     *         @OA\Property(property="accountName", type="string"),
     *         @OA\Property(property="countryCode", type="string", example="DO"),
     *         @OA\Property(property="isDefault", type="boolean")
     *     )),
     *     @OA\Response(response=200, description="Cuenta creada")
     * )
     */
    public function storeBankDetail(Request $req, AlfredService $alfred, string $customerId)
    {
        try {
            $payload = $req->validate([
                'type'              => 'required|string|max:32',
                'accountNumber'     => 'required|string|max:64',
                'accountType'       => 'required|string|max:32',
                'accountName'       => 'required|string|max:150',
                'accountBankCode'   => 'nullable|string|max:32',
                'accountAlias'      => 'nullable|string|max:64',
                'networkIdentifier' => 'nullable|string|max:64',
                'bankStreet'        => 'nullable|string|max:120',
                'bankCity'          => 'nullable|string|max:80',
                'bankState'         => 'nullable|string|max:80',
                'bankCountry'       => 'nullable|string|max:80',
                'bankPostalCode'    => 'nullable|string|max:20',
                'routingNumber'     => 'nullable|string|max:32',
                'iban'              => 'nullable|string|max:64',
                'bic'               => 'nullable|string|max:32',
                'countryCode'       => 'nullable|string|max:8',
                'isDefault'         => 'nullable|boolean',
            ]);

            $created = $alfred->createCustomerBankDetail($customerId, $payload);

            if (! empty($payload['isDefault']) && ! empty($created['id'])) {
                $alfred->setDefaultCustomerBankDetail($customerId, (string) $created['id']);
            }

            return response()->json(['ok' => true, 'account' => $created], 200);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }
    }
}
