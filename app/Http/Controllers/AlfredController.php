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
        $data = $req->validate([ /* amount, pair, etc. */]);
        return response()->json($alfred->createQuote($data));
    }

    public function createOnramp(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([ /* quote_id, amount, etc. */]);
        return response()->json($alfred->createOnramp($data));
    }

    public function createOfframp(Request $req, AlfredService $alfred)
    {
        $data = $req->validate([ /* similar a onramp */]);
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
