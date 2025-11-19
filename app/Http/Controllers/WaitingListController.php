<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
            'client_name' => 'required|string|max:255',
            'client_last_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:35',
            'client_email' => 'required|email',
        ], [
            'client_id.required' => 'El ID del cliente es requerido',
            'client_id.exists' => 'El cliente especificado no existe en la base de datos',
        ]);

        if ($validator->fails()) {
            Log::error('Error de validación: ' . $validator->errors());
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }
        

        //valida que no se haya agregado el cliente a la waiting list
        $waitingList = WaitingList::where('client_id', $request->client_id)->first();
        if ($waitingList) {
            Log::error('El cliente ya se ha agregado a la lista de espera');
            return response()->json([
                'ok' => false,
                'message' => 'El cliente ya se ha agregado a la lista de espera'
            ], 400);
        }
    //Actualiza los datos del cliente en la tabla clients
        $client = Client::find($request->client_id);
        $client->name = $request->client_name;
        $client->last_name = $request->client_last_name;
        $client->phone = $request->client_phone;
        $client->email = $request->client_email;
        $client->save();
        try {
            $waitingList = WaitingList::create($request->all());
            return response()->json([
                'ok' => true,
                'message' => 'Cliente agregado a la lista de espera exitosamente',
                'data' => $waitingList
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al agregar el cliente a la lista de espera: ' . $e->getMessage());
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

    //buscar lista de espera por client_id
    public function getWaitingListByClientId($clientId)
    {
        $waitingList = WaitingList::with('client')->where('client_id', $clientId)->first();
        //si no existe, retorna un mensaje de error
        if (!$waitingList) {
            return response()->json([
                'ok' => false,
                'message' => 'El cliente no se encuentra en la lista de espera'
            ], 404);
        }
        return response()->json($waitingList);
    }
}
