<?php

namespace App\Http\Controllers;

use App\Services\SirenaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            'user_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        $params = [
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'note' => $request->note ?? ''
        ];

        $result = $this->sirenaService->requestBonus($params);
        
        return response()->json($result);
    }
}
