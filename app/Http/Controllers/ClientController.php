<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Ramsey\Uuid\Uuid;

class ClientController extends Controller
{
    /**
     * Crear un nuevo cliente
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'required|string|max:20',
                'card_number_id' => 'nullable|string|max:255'
            ]);
            // vamos a hacer la misma validacion para el $params['receiver_phone']
            $phone = preg_replace('/[^0-9]/', '', $request->phone);
            if (substr($phone, 0, 1) !== '1') {
                $phone = '1' . $phone;
            }
            $phone = preg_replace('/[^0-9]/', '', $phone);
            
            //valida si el cliente existe con el mismo phone y retorna el cliente
            $client = Client::where('phone', $phone)->first();
            
            if ($client) {
                return response()->json($client);
            }
            
            // Generar UUID automáticamente
            $request->merge(['uuid' => Uuid::uuid4()->toString()]);

            // Establecer status por defecto como 'active'
            $request->merge(['status' => true]);

            $client = Client::create($request->all());
            $client->phone = $phone;
            $client->save();

            return response()->json($client);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al crear el cliente: ' . $e->getMessage()
            ], 500);
        }
    }   

    /**
     * Obtener todos los clientes
     */
    public function getClients(): JsonResponse
    {
        try {
            $clients = Client::all();

            return response()->json($clients);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener los clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cliente por teléfono
     */
    public function getClientbyPhone($phone): JsonResponse
    {
        try {
            $client = Client::where('phone', $phone)->first();

            if (!$client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cliente no encontrado',
                    'data' => null
                ], 404);
            }

            return response()->json($client);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al buscar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cliente por UUID
     */
    public function getClientByUuid($uuid): JsonResponse
    {
        try {
            $client = Client::where('uuid', $uuid)->first();

            if (!$client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cliente no encontrado',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'data' => $client
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al buscar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}
