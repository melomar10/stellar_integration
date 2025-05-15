<?php

namespace App\Http\Controllers;

use App\Services\BridgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        //return response()->json($data);
        return response()->json($bridge->createCustomer($data));
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

        return response()->json($bridge->generateKycLink($data));
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

        // Castea el fee a string si viene
        if (isset($data['developer_fee_percent'])) {
            $data['developer_fee_percent'] = (string) $data['developer_fee_percent'];
        }

        return response()->json(
            $bridge->createVirtualAccount($id, $data)
        );
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
