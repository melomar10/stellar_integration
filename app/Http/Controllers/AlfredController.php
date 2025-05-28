<?php

namespace App\Http\Controllers;

use App\Services\AlfredService;
use Illuminate\Http\Request;

class AlfredController extends Controller
{
    public function createCustomer(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([
            'email'       => 'required|email',
            'phoneNumber' => 'required|string',
        ]);

        return response()->json($alfred->createCustomer($data));
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

    public function createQuote(Request $req, AlfredService $alfred)
    {
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

        return response()->json($alfred->createQuote($data));
    }

    public function createOnramp(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([ /* quote_id, amount, etc. */]);
        return response()->json($alfred->createOnramp($data));
    }
    
    public function createPaymentMethod(Request $req, AlfredService $alfred)
    {
      
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

        return response()->json($alfred->createPaymentMethod($data));
    }

    public function getPaymentMethods(Request $req, AlfredService $alfred)
    {
        $customerId = $req->query('customerId');

        if (empty($customerId)) {
            return response()->json([
                'message' => 'El parámetro customerId es requerido.'
            ], 422);
        }

        return response()->json($alfred->getPaymentMethods($customerId));
    }
    
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

    public function createSupport(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([ /* subject, message, etc. */]);
        return response()->json($alfred->createSupportTicket($data));
    }

    public function createCustomerCountry(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([
            'email'       => 'required|email',
            'phoneNumber' => 'required|string',
            'country'     => 'required|string',
        ]);
        
        return response()->json($alfred->createCustomerCountry($data));
    }
}
