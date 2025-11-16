<?php

namespace App\Http\Controllers\Flows;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\Flows\StepByFlow;

class StepByFlowController extends Controller
{
    public function createStepByFlow(Request $request)
    {
        //crea una validacion que retorne el error para API
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
        ]);

        //valida si el cliente existe
        $client = Client::find($request->client_id);
        if (!$client) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }
        $stepByFlow = StepByFlow::create($request->all());
        return response()->json($stepByFlow);
    }
//traer los steps by flow paginados y ordenados por created_at descendente
    public function getStepByFlows(Request $request)
    {
        $stepByFlows = StepByFlow::orderBy('created_at', 'desc')->paginate($request->per_page ?? 10);
        return response()->json($stepByFlows);
    }
    //traer los steps by flow por client_id paginados y ordenados por created_at descendente
    public function getStepByFlowsByClientId(Request $request, $clientId)
    {
        // Validar que el cliente existe
        $client = Client::find($clientId);
        if (!$client) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }
        
        $stepByFlows = StepByFlow::where('client_id', $clientId)->orderBy('created_at', 'desc')->paginate($request->per_page ?? 10);
        return response()->json($stepByFlows);
    }
    //trae a los clientes que tienen steps by flow paginados y ordenados por created_at descendente
    public function getClientStepByFlows(Request $request)
    {
        //ordenalos por el ultimo step by flow creado
        $clients = Client::with(['stepByFlows' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->whereHas('stepByFlows')
        ->orderBy('created_at', 'desc')
        ->paginate($request->per_page ?? 10);
        
        return response()->json($clients);
    }
    //trae todos los step by flow por client_id paginados y ordenados por created_at descendente
    public function getStepByFlowByClientId(Request $request, $clientId)
    {
        $stepByFlows = StepByFlow::where('client_id', $clientId)->orderBy('created_at', 'desc')->paginate($request->per_page ?? 10);
        return response()->json($stepByFlows);
    }
}
