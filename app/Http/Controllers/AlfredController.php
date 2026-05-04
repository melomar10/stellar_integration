<?php

namespace App\Http\Controllers;

use App\Models\AlfredAccount;
use App\Models\Client;
use App\Services\AlfredService;
use App\Services\FlowService;
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
                'onRampCountry' => $onRampCountry,
                'offRampCountry' => $offRampCountry,
                'onRampCurrency' => $onRampCurrency,
                'offRampCurrency' => $offRampCurrency,
                'role' => $role,
                'emailVerified' => false,
                'phoneNumberVerified' => false,
                'extras' => $flowJson['extras'] ?? ['flow_token' => ($flowJson['flow_token'] ?? null)],
                'fileTypes' => $flowJson['fileTypes'] ?? null,
                'files' => $flowJson['files'] ?? [],
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
 *     path="/api/quote",
 *     summary="Crear una nueva cotización (Quote)",
 *     operationId="createQuote",
 *     tags={"Quotes"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Datos necesarios para crear una cotización",
 *         @OA\JsonContent(
 *             required={"fromCurrency", "toCurrency", "paymentMethodType", "chain", "fromAmount"},
 *             @OA\Property(property="fromCurrency", type="string", example="USD", minLength=3, maxLength=3),
 *             @OA\Property(property="toCurrency", type="string", example="DO", minLength=2, maxLength=3),
 *             @OA\Property(property="paymentMethodType", type="string", example="BANK"),
 *             @OA\Property(property="chain", type="string", example="XLM"),
 *             @OA\Property(property="fromAmount", type="number", format="float", example=100)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Quote creada exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="quoteId", type="string", example="quote_abc123"),
 *             @OA\Property(property="fromAmount", type="number", example=100),
 *             @OA\Property(property="toAmount", type="number", example=5700),
 *             @OA\Property(property="rate", type="number", example=57.0)
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
 *             @OA\Property(property="message", type="string", example="Error al Crear la Quote"),
 *             @OA\Property(property="error", type="string", example="Detalle del error")
 *         )
 *     )
 * )
 */
    public function createQuote(Request $req, AlfredService $alfred)
    {
        try{
            $req->merge([
                'fromCurrency'       => $req->input('fromCurrency', 'USD'),
                'toCurrency'         => $req->input('toCurrency', 'DO'),
                'paymentMethodType'  => $req->input('paymentMethodType', 'BANK'),
                'chain'              => $req->input('chain', 'DO'),
            ]);

            $data = $req->validate([
                'fromCurrency'       => 'required|string|size:3',
                'toCurrency'         => 'required|string|size:3',
                'paymentMethodType'  => 'required|string|max:20',
                'chain'              => 'required|string|max:10',
                'fromAmount'         => 'required|numeric|min:10|max:1000', // Min 10, Max 1000 USDT
            ]);

           $dto = $alfred->createQuote($data);
           return response()->json((array) $dto, 200);
        }catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al Crear la Quote',
                    'error' => $e->getMessage(),
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

    public function submitKycForm(Request $req, AlfredService $alfred)
    {
        try {
            $data = $req->validate([
                'firstName'              => 'required|string|max:100',
                'middleName'             => 'nullable|string|max:100',
                'lastName'               => 'required|string|max:100',
                'email'                  => 'required|email|max:150',
                'phoneNumber'            => 'required|string|max:30',
                'dateOfBirth'            => 'nullable|date',
                'placeOfBirth'           => 'nullable|string|max:150',
                'gender'                 => 'nullable|string|in:Male,Female,Other',
                'mainNationality'        => 'nullable|string|max:5',
                'secondaryNationalities' => 'nullable|string|max:100',
                'preferredLanguage'      => 'nullable|string|max:10',
                'isPep'                  => 'nullable|string|in:true,false',
                'streetAddress'          => 'nullable|string|max:255',
                'streetAddressLine2'     => 'nullable|string|max:255',
                'residentialAddress'     => 'nullable|string|max:255',
                'city'                   => 'nullable|string|max:100',
                'stateProvinceRegion'    => 'nullable|string|max:100',
                'country'                => 'nullable|string|max:50',
                'postalCode'             => 'nullable|string|max:20',
                'nationalId'             => 'nullable|string|max:50',
                'licenseId'              => 'nullable|string|max:50',
                'dni'                    => 'nullable|string|max:50',
                'cpf'                    => 'nullable|string|max:20',
                'move_type'              => 'required|string|in:Enviar,Recibir',
                'idFront'                => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'passport'               => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            ]);

            // Al menos un documento requerido
            if (!$req->hasFile('idFront') && !$req->hasFile('passport')) {
                return back()
                    ->withErrors(['documents' => 'Debes subir al menos un documento de identidad (Cédula/ID o Pasaporte).'])
                    ->withInput();
            }

            Log::debug('Alfred submitKycForm input', array_diff_key($data, ['idFront' => 1, 'passport' => 1]));

            // Normalizar teléfono
            $phone = preg_replace('/[^0-9]/', '', (string) $data['phoneNumber']);
            if (substr($phone, 0, 1) !== '1') {
                $phone = '1' . $phone;
            }

            // Crear o actualizar cliente local
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
                    'country'    => $data['country'] ?? 'DO',
                ]);
            } else {
                $client->update([
                    'name'      => $data['firstName'],
                    'last_name' => $data['lastName'],
                    'email'     => $data['email'],
                    'country'   => $data['country'] ?? $client->country,
                ]);
            }
            Log::debug('Alfred submitKycForm client', [$client->toArray()]);

            // Buscar customer en Alfred por teléfono antes de intentar crear
            $search = $alfred->searchCustomers(['phone' => $phone, 'isActive' => 'true']);
            Log::debug('Alfred searchCustomers by phone', ['phone' => $phone, 'response' => $search]);
            $existingItems = is_array($search) ? ($search['items'] ?? []) : [];

            if (!empty($existingItems[0]) && is_array($existingItems[0])) {
                $customer = $existingItems[0];
                Log::debug('Alfred customer found (reusing)', ['id' => $customer['id'] ?? null]);
            } else {
                $createResp = $alfred->createCustomerRaw([
                    'firstName'   => $data['firstName'],
                    'lastName'    => $data['lastName'],
                    'email'       => $data['email'],
                    'phone'       => $phone,
                    'address'     => $data['streetAddress'] ?? null,
                    'city'        => $data['city']          ?? null,
                    'country'     => $data['country']       ?? 'DO',
                    'postalCode'  => $data['postalCode']    ?? null,
                    'dateOfBirth' => $data['dateOfBirth']   ?? null,
                    'isActive'    => true,
                ]);
                Log::debug('Alfred createCustomerRaw response', [$createResp]);
                $customer = $createResp;
            }

            $customerId = $customer['id'] ?? null;
            if (!$customerId) {
                return back()
                    ->withErrors(['documents' => 'No se obtuvo un ID de cliente de Alfred. Intenta de nuevo.'])
                    ->withInput();
            }

            // Determinar parámetros ramp según move_type
            $moveType = $data['move_type'];
            if ($moveType === 'Enviar') {
                $onRampCountry   = 'US'; $offRampCountry   = 'DO';
                $onRampCurrency  = 'USD'; $offRampCurrency = 'DOP';
                $role            = 'sender';
            } else {
                $onRampCountry   = 'DO'; $offRampCountry   = 'US';
                $onRampCurrency  = 'DOP'; $offRampCurrency = 'USD';
                $role            = 'receiver';
            }

            // Consultar estado KYC del customer (campos pendientes)
            try {
                $kycStatus = $alfred->getKycCustomerStatus((string) $customerId, [
                    'onRampCountry'   => $onRampCountry,
                    'offRampCountry'  => $offRampCountry,
                    'onRampCurrency'  => $onRampCurrency,
                    'offRampCurrency' => $offRampCurrency,
                    'role'            => $role,
                ]);
                Log::debug('Alfred submitKycForm kycStatus', [$kycStatus]);
            } catch (\Throwable $e) {
                Log::warning('Alfred submitKycForm kycStatus failed (non-blocking)', ['error' => $e->getMessage()]);
                $kycStatus = [];
            }

            // Almacenar archivos y armar array de rutas + fileTypes
            $namedFiles = [];
            $fileTypes  = [];
            $fileFields = ['idFront' => 'idFront', 'passport' => 'passport'];
            foreach ($fileFields as $inputName => $apiFieldName) {
                if ($req->hasFile($inputName) && $req->file($inputName)->isValid()) {
                    $path = $req->file($inputName)->store('kyc_tmp', 'local');
                    $namedFiles[$apiFieldName] = storage_path("app/{$path}");
                    $fileTypes[] = $apiFieldName === 'idFront' ? 'id_front' : 'passport';
                    Log::debug("Alfred submitKycForm stored file [{$apiFieldName}]", ['path' => $namedFiles[$apiFieldName]]);
                }
            }

            // Enviar KYC con todos los campos del API + archivos
            $kycPayload = array_filter([
                'firstName'              => $data['firstName'],
                'middleName'             => $data['middleName']             ?? null,
                'lastName'               => $data['lastName'],
                'email'                  => $data['email'],
                'phoneNumber'            => '+' . $phone,
                'gender'                 => $data['gender']                 ?? null,
                'dateOfBirth'            => $data['dateOfBirth']            ?? null,
                'placeOfBirth'           => $data['placeOfBirth']           ?? null,
                'mainNationality'        => $data['mainNationality']        ?? null,
                'secondaryNationalities' => $data['secondaryNationalities'] ?? null,
                'preferredLanguage'      => $data['preferredLanguage']      ?? null,
                'isPep'                  => $data['isPep']                  ?? 'false',
                'streetAddress'          => $data['streetAddress']          ?? null,
                'streetAddressLine2'     => $data['streetAddressLine2']     ?? null,
                'residentialAddress'     => $data['residentialAddress'] ?? ($data['streetAddress'] ?? null),
                'city'                   => $data['city']                   ?? null,
                'stateProvinceRegion'    => $data['stateProvinceRegion']    ?? null,
                'country'                => $data['country']                ?? null,
                'postalCode'             => $data['postalCode']             ?? null,
                'nationalId'             => $data['nationalId']             ?? null,
                'licenseId'              => $data['licenseId']              ?? null,
                'dni'                    => $data['dni']                    ?? null,
                'cpf'                    => $data['cpf']                    ?? null,
                'onRampCountry'          => $onRampCountry,
                'offRampCountry'         => $offRampCountry,
                'onRampCurrency'         => $onRampCurrency,
                'offRampCurrency'        => $offRampCurrency,
                'role'                   => $role,
                'emailVerified'          => 'false',
                'phoneNumberVerified'    => 'false',
                'fileTypes'              => implode(',', $fileTypes),
                'files'                  => json_encode([new \stdClass()]),
                'extras'                 => json_encode(['source' => 'kyc_form']),
            ], static fn ($v) => !is_null($v) && $v !== '');

            $kycResponse = $alfred->KYC((string) $customerId, $kycPayload, $namedFiles);

            Log::debug('Alfred submitKycForm kycResponse', [$kycResponse]);

            // Limpiar archivos temporales
            foreach ($namedFiles as $path) {
                @unlink($path);
            }

            // Guardar AlfredAccount
            $alfredAccount = AlfredAccount::updateOrCreate(
                ['alfred_customer_id' => (string) $customerId],
                [
                    'first_name'             => $data['firstName'],
                    'middle_name'            => $data['middleName']          ?? null,
                    'last_name'              => $data['lastName'],
                    'email'                  => $data['email'],
                    'phone'                  => $customer['phone']           ?? $data['phoneNumber'],
                    'city'                   => $customer['city']            ?? ($data['city'] ?? null),
                    'country'                => $customer['country']         ?? ($data['country'] ?? null),
                    'postal_code'            => $customer['postalCode']      ?? ($data['postalCode'] ?? null),
                    'gender'                 => $data['gender']              ?? null,
                    'date_of_birth'          => $data['dateOfBirth']         ?? null,
                    'place_of_birth'         => $data['placeOfBirth']        ?? null,
                    'main_nationality'       => $data['mainNationality']     ?? null,
                    'secondary_nationalities'=> $data['secondaryNationalities'] ?? null,
                    'preferred_language'     => $data['preferredLanguage']   ?? null,
                    'is_pep'                 => ($data['isPep'] ?? 'false') === 'true',
                    'street_address'         => $data['streetAddress']       ?? null,
                    'street_address_line2'   => $data['streetAddressLine2']  ?? null,
                    'residential_address'    => $data['residentialAddress']  ?? null,
                    'state_province_region'  => $data['stateProvinceRegion'] ?? null,
                    'address'                => $data['streetAddress']       ?? null,
                    'national_id'            => $data['nationalId']          ?? null,
                    'license_id'             => $data['licenseId']           ?? null,
                    'dni'                    => $data['dni']                 ?? null,
                    'cpf'                    => $data['cpf']                 ?? null,
                    'file_types'             => implode(',', $fileTypes),
                    'move_type'              => $moveType,
                    'role'                   => $role,
                    'on_ramp_country'        => $onRampCountry,
                    'off_ramp_country'       => $offRampCountry,
                    'on_ramp_currency'       => $onRampCurrency,
                    'off_ramp_currency'      => $offRampCurrency,
                    'is_active'              => true,
                    'kyc_id'                 => $kycResponse['kycId'] ?? ($kycResponse['id'] ?? null),
                ]
            );

            $client->alfred_account_id = $alfredAccount->id;
            $client->save();

            return redirect()->route('alfred.kyc.form')
                ->with('success', 'Verificación enviada correctamente. ID de cliente Alfred: ' . $customerId);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Alfred submitKycForm error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return back()
                ->withErrors(['documents' => 'Error al procesar la verificación: ' . $e->getMessage()])
                ->withInput();
        }
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

            // 3. Crear Quote
            $quote = $alfred->createQuote([
                'fromCurrency' => $request->fromCurrency,
                'toCurrency' => $request->toCurrency,
                'chain' => $request->chain,
                'fromAmount' => $request->amount,
                'toAmount' => $request->amount,
                'paymentMethodType' => 'BANK',
            ]);

            // 4. Ejecutar Offramp
            $offramp = $alfred->createOfframp([
                'quoteId' => $quote->quoteId,
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


}
