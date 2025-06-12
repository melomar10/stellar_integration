<?php

namespace App\Http\Controllers;

use App\Services\BridgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class BridgeController extends Controller
{
     /**
     * @OA\Post(
     *     path="/api/bridge/customers",
     *     summary="Crear cliente",
     *     tags={"bridge"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "first_name", "last_name", "email", "phone", "birth_date", "signed_agreement_id"},
     *             @OA\Property(property="type", type="string", example="individual"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="signed_agreement_id", type="string", example="abc123"),
     *             @OA\Property(property="residential_address", type="object",
     *                 @OA\Property(property="street_line_1", type="string", example="123 Main St"),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="country", type="string", example="US")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cliente creado correctamente"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function createCustomer(Request $req, BridgeService $bridge)
    {

        $data = $req->validate([
            'type'                      => 'required|in:individual,business',
            'first_name'                => 'required|string',
            'last_name'                 => 'required|string',
            'email'                     => 'required|email',
            'phone'                     => 'required|string',
            'residential_address.street_line_1'     => 'required|string',
            'residential_address.city'              => 'required|string',
            'residential_address.country'           => 'required|string',
            'birth_date'                => 'required|date',
            'signed_agreement_id'       => 'required|string',
        ]);
        //return response()->json($data);
        return response()->json($bridge->createCustomer($data));
    }

    /**
     * @OA\Post(
     *     path="/api/bridge/customers/kyc-link",
     *     summary="Generar link KYC",
     *     tags={"bridge"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name", "email", "type"},
     *             @OA\Property(property="full_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="type", type="string", enum={"individual", "business"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Link generado correctamente"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function generateKycLink(Request $req, BridgeService $bridge)
    {
        $validated = $req->validate([
            'full_name' => 'required|string',
            'email'     => 'required|email',
            'type'      => 'required|in:individual,business',
        ]);

        // Agrega dinámicamente el redirect_uri apuntando a nuestra vista de callback
        $data = array_merge($validated, [
            'redirect_uri' => route('bridge.kyc.callback'),
        ]);

        return response()->json($bridge->generateKycLink($data));
    }

 /**
     * @OA\Post(
     *     path="/api/bridge/customers/{id}/va",
     *     summary="Crear cuenta virtual",
     *     tags={"bridge"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"source", "destination"},
     *             @OA\Property(property="source", type="object",
     *                 @OA\Property(property="currency", type="string")
     *             ),
     *             @OA\Property(property="destination", type="object",
     *                 @OA\Property(property="payment_rail", type="string"),
     *                 @OA\Property(property="currency", type="string"),
     *                 @OA\Property(property="address", type="string")
     *             ),
     *             @OA\Property(property="developer_fee_percent", type="number")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cuenta virtual creada")
     * )
     */
    public function createVirtualAccount(Request $req, BridgeService $bridge, $id)
    {
        $data = $req->validate([
            'source.currency'            => 'required|string',
            'destination.payment_rail'   => 'required|string',
            'destination.currency'       => 'required|string',
            'destination.address'        => 'required|string',
            'developer_fee_percent'      => 'nullable|numeric',
        ]);

        // Castea el fee a string si viene
        if (isset($data['developer_fee_percent'])) {
            $data['developer_fee_percent'] = (string) $data['developer_fee_percent'];
        }

        return response()->json(
            $bridge->createVirtualAccount($id, $data)
        );
    }

 /**
     * @OA\Post(
     *     path="/api/bridge/transfers",
     *     summary="Crear transferencia",
     *     tags={"bridge"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "on_behalf_of", "source", "destination"},
     *             @OA\Property(property="amount", type="number", example=100),
     *             @OA\Property(property="on_behalf_of", type="string", example="partner_id"),
     *             @OA\Property(property="source", type="object",
     *                 @OA\Property(property="payment_rail", type="string"),
     *                 @OA\Property(property="currency", type="string"),
     *                 @OA\Property(property="from_address", type="string"),
     *                 @OA\Property(property="external_account_id", type="string")
     *             ),
     *             @OA\Property(property="destination", type="object",
     *                 @OA\Property(property="payment_rail", type="string"),
     *                 @OA\Property(property="currency", type="string"),
     *                 @OA\Property(property="external_account_id", type="string"),
     *                 @OA\Property(property="to_address", type="string")
     *             ),
     *             @OA\Property(property="developer_fee_percent", type="number")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transferencia creada")
     * )
     */
    public function createTransfer(Request $req, BridgeService $bridge,AlfredService $alfred)
    {
            $data = $req->validate([
            // Datos para Alfred (Offramp)
            'email'            => 'nullable|email',
            'phoneNumber'      => 'required|string',
            'country'          => 'nullable|string',
            'accountNumber'    => 'nullable|string',
            'accountType'      => 'nullable|string',
            'chain'            => 'nullable|string',

            // Datos para Transfer (Bridge)
            'amount'                          => 'required|numeric',
            'on_behalf_of'                    => 'required|string',
            'source.payment_rail'             => 'required|string',
            'source.currency'                 => 'required|string',
            'source.from_address'             => 'nullable|string',
            'source.external_account_id'      => 'nullable|string',
            'destination.payment_rail'        => 'required|string',
            'destination.currency'            => 'required|string',
            'destination.external_account_id' => 'nullable|string',
            'destination.to_address'          => 'nullable|string',
            'developer_fee_percent'           => 'nullable|numeric',
        ]);


       // 🔄 Data para Alfred (Offramp)
        $alfredData = [
            'email'         => $data['email'] ?? null,
            'phoneNumber'   => $data['phoneNumber'],
            'country'       => $data['country'] ?? null,
            'accountNumber' => $data['accountNumber'] ?? null,
            'accountType'   => $data['accountType'] ?? null,
            'chain'         => $data['chain'] ?? 'DO',
            'amount'        => $data['amount'],
            'fromCurrency'  => $data['source']['currency'],
            'toCurrency'    => $data['destination']['currency'],
        ];

        // 🔄 Data para Bridge (Transfer)
        $bridgeData = [
            'amount'                          => $data['amount'],
            'on_behalf_of'                    => $data['on_behalf_of'],
            'source' => [
                'payment_rail'        => $data['source']['payment_rail'],
                'currency'            => $data['source']['currency'],
                'from_address'        => $data['source']['from_address'] ?? null,
                'external_account_id' => $data['source']['external_account_id'] ?? null,
            ],
            'destination' => [
                'payment_rail'        => $data['destination']['payment_rail'],
                'currency'            => $data['destination']['currency'],
                'external_account_id' => $data['destination']['external_account_id'] ?? null,
                'to_address'          => $data['destination']['to_address'] ?? null,
            ],
            'developer_fee_percent' => $data['developer_fee_percent'] ?? null,
        ];

        try {
          
             $transferResult = $bridge->createTransfer($bridgeData);
             $offrampResult = $alfred->handleOfframp($alfredData);
            return response()->json([
                'success' => true,
                'offramp' => $offrampResult,
                'transfer' => $transferResult,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error en createTransfer', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

    }
  /**
     * @OA\Get(
     *     path="/api/bridge/customers/tos-links",
     *     summary="Generar ToS Link",
     *     tags={"bridge"},
     *     @OA\Response(response=200, description="Link generado")
     * )
     */
    // Generate ToS Link
    public function generateTosLink(BridgeService $bridge)
    {
        return response()->json($bridge->generateTosLink());
    }

    public function tosCallback(BridgeService $bridge)
    {
        // Llamas al servicio y obtienes solo 'url'
        $response = $bridge->generateTosLink();

        $tosUrl = $response['url'];
        // parsear el query string para sacar session_token
        $parts = parse_url($tosUrl);
        parse_str($parts['query'] ?? '', $query);

        if (! isset($query['session_token'])) {
            abort(500, 'No se encontró session_token en la URL de ToS');
        }

        // Guardas en sesión (o base) para usar después
        session(['signed_agreement_id' => $query['session_token']]);

        // Envías la URL a la vista de ToS
        return view('kyc.tos', ['tos_link' => $tosUrl]);
    }

    public function kycCallback(Request $req)
    {
        Log::info('KYC Callback', [
            'request' => $req->all(),
        ]);
        // Bridge te pasa signed_agreement_id aquí
        $signedId = $req->query('signed_agreement_id');

        if (! $signedId) {
            return abort(400, 'No se recibió signed_agreement_id');
        }

        // Guárdalo en sesión (o DB) para usarlo al crear el cliente
        session(['signed_agreement_id' => $signedId]);

        // Redirige al formulario de creación de cliente
        return redirect()->route('bridge.customers.form');
    }

    public function showTos(BridgeService $bridge)
    {
        $resp   = $bridge->generateTosLink();
        $tosUrl = $resp['url'];   // ej. https://dashboard.bridge.xyz/accept-terms-of-service?session_token=...

        return view('kyc.tos', compact('tosUrl'));
    }
}
