<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WaitingListController extends Controller
{
    public function getWaitingList(Request $request)
    {
        //traer todos los clientes que estan en la waiting list paginados y ordenados por created_at descendente
        $waitingList = WaitingList::with('client')->orderBy('created_at', 'desc')->paginate($request->per_page ?? 10);
        return response()->json($waitingList);
    }
    public function addClientToWaitingList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
        ], [
            'client_id.required' => 'El ID del cliente es requerido',
            'client_id.exists' => 'El cliente especificado no existe en la base de datos',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $waitingList = WaitingList::create($request->all());
            return response()->json([
                'ok' => true,
                'message' => 'Cliente agregado a la lista de espera exitosamente',
                'data' => $waitingList
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al agregar el cliente a la lista de espera: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getWaitingListById($id)
    {
        $waitingList = WaitingList::with('client')->find($id);
        return response()->json($waitingList);
    }
}
