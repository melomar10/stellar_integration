<?php

namespace App\Http\Controllers;

use App\Services\BridgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\VirtualAccount;

class BridgeController extends Controller
{
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

        // Buscar si ya existe un cliente con el mismo email
        $existingCustomer = Customer::where('email', $data['email'])->first();

        // Si el cliente existe y tiene bridge_customer_id, retornar mensaje
        if ($existingCustomer && $existingCustomer->bridge_customer_id) {
            return response()->json([
                'message' => 'El cliente ya existe en Bridge',
                'customer' => $existingCustomer,
                'status' => 'existing'
            ], 200);
        }

        // Si el cliente existe pero no tiene bridge_customer_id, actualizar sus datos
        if ($existingCustomer) {
            $existingCustomer->update([
                'type' => $data['type'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'street_line_1' => $data['residential_address']['street_line_1'],
                'city' => $data['residential_address']['city'],
                'country' => $data['residential_address']['country'],
                'birth_date' => $data['birth_date'],
                'signed_agreement_id' => $data['signed_agreement_id']
            ]);
            $customer = $existingCustomer;
        } else {
            // Crear el cliente en la base de datos
            $customer = Customer::create([
                'type' => $data['type'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'street_line_1' => $data['residential_address']['street_line_1'],
                'city' => $data['residential_address']['city'],
                'country' => $data['residential_address']['country'],
                'birth_date' => $data['birth_date'],
                'signed_agreement_id' => $data['signed_agreement_id']
            ]);
        }

        // Enviar los datos a Bridge
        $bridgeResponse = $bridge->createCustomer($data);

        // Actualizar el bridge_customer_id si la respuesta fue exitosa
        if (isset($bridgeResponse['id'])) {
            $customer->update(['bridge_customer_id' => $bridgeResponse['id']]);
        }

        return response()->json($bridgeResponse);
    }

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

        try {
            $response = $bridge->generateKycLink($data);
            return response()->json([
                'kyc_link' => $response['kyc_link']
            ], 200);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorResponse = json_decode($e->response->body(), true);
            
            // Si el error es por enlace duplicado y contiene un enlace existente
            if (isset($errorResponse['existing_kyc_link']['kyc_link'])) {
                return response()->json([
                    'message' => $errorResponse['message'],
                    'kyc_link' => $errorResponse['existing_kyc_link']['kyc_link']
                ], 200);
            }

            return response()->json([
                'error' => 'No se pudo generar el enlace KYC',
                'details' => $errorResponse
            ], 400);
        }
    }

    public function createVirtualAccount(Request $req, BridgeService $bridge, $id)
    {
        $data = $req->validate([
            'source.currency'            => 'required|string',
            'destination.payment_rail'   => 'required|string',
            'destination.currency'       => 'required|string',
            'destination.address'        => 'required|string',
            'developer_fee_percent'      => 'nullable|numeric',
        ]);

        //validar si el customer_id existe
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Validar si el cliente ha aceptado los términos de servicio
        if (!$customer->signed_agreement_id) {
            return response()->json([
                'error' => 'El cliente debe aceptar los términos de servicio primero',
                'code' => 'has_not_accepted_tos',
                'tos_link' => route('bridge.kyc.tos', $id)
            ], 400);
        }

        try {
            // Crear la cuenta virtual en la base de datos
            $virtualAccount = VirtualAccount::create([
                'customer_id' => $customer->id,
                'source_currency' => $data['source']['currency'],
                'destination_payment_rail' => $data['destination']['payment_rail'],
                'destination_currency' => $data['destination']['currency'],
                'destination_address' => $data['destination']['address'],
                'developer_fee_percent' => isset($data['developer_fee_percent']) ? (string) $data['developer_fee_percent'] : null
            ]);

            // Enviar los datos a Bridge
            $bridgeResponse = $bridge->createVirtualAccount($customer->bridge_customer_id, $data);

            // Actualizar el bridge_virtual_account_id si la respuesta fue exitosa
            if (isset($bridgeResponse['id'])) {
                $virtualAccount->update(['bridge_virtual_account_id' => $bridgeResponse['id']]);
            }

            return response()->json($bridgeResponse);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorResponse = json_decode($e->response->body(), true);
            
            // Si el error es por términos de servicio no aceptados
            if (isset($errorResponse['code']) && $errorResponse['code'] === 'has_not_accepted_tos') {
                $tosLink = $bridge->generateTosLink($customer->id);
                return response()->json([
                    'error' => 'El cliente debe aceptar los términos de servicio primero',
                    'code' => 'has_not_accepted_tos',
                    'tos_url' => $tosLink['url']
                ], 400);
            }

            return response()->json([
                'error' => 'Error al crear la cuenta virtual',
                'details' => $errorResponse
            ], 400);
        }
    }

    public function createTransfer(Request $req, BridgeService $bridge)
    {
        $data = $req->validate([
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

        return response()->json($bridge->createTransfer($data));
    }

    // Generate ToS Link
    public function generateTosLink(BridgeService $bridge, $id)
    {
        return response()->json($bridge->generateTosLink($id));
    }

    public function tosCallback(BridgeService $bridge, $id)
    {
        // Llamas al servicio y obtienes solo 'url'
        $response = $bridge->generateTosLink($id);

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

    public function showTos(BridgeService $bridge, $id)
    {
        $resp   = $bridge->generateTosLink($id);
        $tosUrl = $resp['url'] . '&session_token=' . $id;   // ej. https://dashboard.bridge.xyz/accept-terms-of-service?session_token=...

        return view('kyc.tos', compact('tosUrl'));
    }
}
