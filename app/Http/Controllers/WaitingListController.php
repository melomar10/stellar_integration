<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WaitingListController extends Controller
{
    public function getWaitingList(Request $request): JsonResponse
    {
        try {
            $query = WaitingList::with('client');

            // Filtro por nombre o teléfono del cliente
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('client', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $waitingList = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'ok' => true,
                'data' => $waitingList->items(),
                'current_page' => $waitingList->currentPage(),
                'last_page' => $waitingList->lastPage(),
                'per_page' => $waitingList->perPage(),
                'total' => $waitingList->total(),
                'from' => $waitingList->firstItem(),
                'to' => $waitingList->lastItem(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener la lista de espera: ' . $e->getMessage()
            ], 500);
        }
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

        //valida que no se haya agregado el cliente a la waiting list
        $waitingList = WaitingList::where('client_id', $request->client_id)->first();
        if ($waitingList) {
            return response()->json([
                'ok' => false,
                'message' => 'El cliente ya se ha agregado a la lista de espera'
            ], 400);
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
