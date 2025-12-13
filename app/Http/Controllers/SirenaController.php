<?php

namespace App\Http\Controllers;

use App\Services\FlowService;
use App\Services\SirenaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SirenaController extends Controller
{
    protected $sirenaService;

    public function __construct(SirenaService $sirenaService)
    {
        $this->sirenaService = $sirenaService;
    }

    /**
     * Obtiene la tasa de cambio y cálculo de recarga
     */
    public function getRechargeResume(Request $request): JsonResponse
    {
        $request->validate([
            'total' => 'required|numeric|min:0'
        ]);

        $result = $this->sirenaService->getRechargeResume($request->total);
        
        return response()->json($result);
    }

    /**
     * Obtiene las sucursales por provincia
     */
    public function getCompaniesByProvince(Request $request, $provinceId): JsonResponse
    {
        $result = $this->sirenaService->getCompaniesByProvince($provinceId);
        
        return response()->json($result);
    }

    /**
     * Solicita un bono/pago con conversión de pesos a dólares
     */
    public function requestBonus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'user_id' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'company' => 'nullable|string',
            'phone_sender' => 'nullable|string',
            'sender_name' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

     //valida si viene el campo flow
     if($request->has('flow')){
        $flowService = new FlowService();
        $flowData = $flowService->convertFlowToJson($request->flow);
        $params = [
            'id' => $request->id,
            'user_id' => $request->user_id,
            'amount' => $flowData['monto'] ?? 0,
            'note' => $request->note ?? '',
            'company' => $request->company ?? 'Sirena',
            'phone_sender' => $flowData['Phone_Number'] ?? $request->phone_sender ?? '',
            'sender_name' => $flowData['Nombre_y_Aprellido_0'] ?? '',
            'type' => $request->type ?? ''
        ];
     }else{
        $params = [
            'id' => $request->id,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'note' => $request->note ?? '',
            'company' => $request->company ?? 'Sirena',
            'phone_sender' => $request->phone_sender ?? '',
            'sender_name' => $request->sender_name ?? '',
            'type' => $request->type ?? ''
        ];
    }

        $result = $this->sirenaService->requestBonus($params);
        Log::info('requestBonus', ['result' => $result]);
        
        return response()->json($result);
    }
}
