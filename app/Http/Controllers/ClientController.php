<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Flows\StepByFlow;
use App\Services\DomiPagoService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'last_name' => 'nullable|string|max:255',
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

            $request->merge(['has_account' => false]);
            $request->merge(['country' => 'DO']);
            
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
     * Obtener todos los clientes con paginación y filtros
     */
    public function getClients(Request $request): JsonResponse
    {
        try {
            // Usar el método compartido para construir la query con filtros
            $query = $this->buildFilteredQuery($request);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $clients = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'ok' => true,
                'data' => $clients->items(),
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'from' => $clients->firstItem(),
                'to' => $clients->lastItem(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener los clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cliente por teléfono
     * Si no existe en la BD local, consulta el servicio externo de DomiPago
     */
    public function getClientbyPhone($phone): JsonResponse
    {
        try {
            // Normalizar teléfono
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (substr($phone, 0, 1) !== '1') {
                $phone = '1' . $phone;
            }
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Buscar cliente en la BD local
            $client = Client::where('phone', $phone)->where('has_account', true)->first();

            if ($client) {
                return response()->json($client);
            }

            // Si no existe en la BD local, consultar el servicio externo
            $domiPagoService = new DomiPagoService();
            $apiResponse = $domiPagoService->getReceiverHasAccount($phone);

            // Verificar si el servicio tiene el cliente registrado
            if (!$apiResponse['ok'] || !$apiResponse['hasAccount']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cliente no encontrado',
                    'data' => null
                ], 404);
            }

            //valida si el cliente existe sin has_account y si es asi actualiza el cliente con los datos del servicio externo
            
            // El cliente existe en el servicio externo, crear en la BD local
            $clientData = $domiPagoService->extractClientData($apiResponse, $phone);
            
            // Generar UUID automáticamente
            $clientData['uuid'] = Uuid::uuid4()->toString();
            
            $client = Client::where('phone', $phone)->where('has_account', false)->first();
            if ($client) {
                $client->update($clientData);
                return response()->json($client);
            }else{
            // Crear el cliente
            $client = Client::create($clientData);
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

    /**
     * Obtener cliente por ID
     */
    public function getClientById($id): JsonResponse
    {
        try {
            $client = Client::find($id);

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

    /**
     * Actualizar un cliente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'required|string|max:20',
                'card_number_id' => 'nullable|string|max:255',
                'status' => 'nullable|boolean'
            ]);

            $client = Client::find($id);

            if (!$client) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Normalizar teléfono
            $phone = preg_replace('/[^0-9]/', '', $request->phone);
            if (substr($phone, 0, 1) !== '1') {
                $phone = '1' . $phone;
            }
            $phone = preg_replace('/[^0-9]/', '', $phone);

            $client->name = $request->name;
            $client->last_name = $request->last_name;
            $client->email = $request->email;
            $client->phone = $phone;
            $client->card_number_id = $request->card_number_id;
            $client->status = $request->has('status') ? (bool)$request->status : $client->status;
            $client->save();

            return response()->json([
                'ok' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data' => $client
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Construye la query con los filtros aplicados
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = Client::query();

        // Filtro por nombre
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filtro por fecha del último step
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $stepName = $request->get('step_name');
        
        // Construir condiciones para el último step
        $hasDateFilter = !empty($dateFrom) || !empty($dateTo);
        $hasStepNameFilter = !empty($stepName);
        
        if ($hasDateFilter || $hasStepNameFilter) {
            // Obtener clientes cuyo último step cumpla con los filtros
            $query->whereIn('id', function($subQuery) use ($dateFrom, $dateTo, $stepName) {
                // Subquery para obtener el último step de cada cliente
                $subQuery->select('client_id')
                         ->from('step_by_flows')
                         ->whereRaw('created_at = (
                             SELECT MAX(created_at) 
                             FROM step_by_flows as s2 
                             WHERE s2.client_id = step_by_flows.client_id
                         )');
                
                if (!empty($dateFrom)) {
                    $subQuery->whereDate('created_at', '>=', $dateFrom);
                }
                if (!empty($dateTo)) {
                    $subQuery->whereDate('created_at', '<=', $dateTo);
                }
                if (!empty($stepName)) {
                    $subQuery->where('name', $stepName);
                }
            });
        }

        return $query;
    }

    /**
     * Exportar clientes a Excel con los mismos filtros aplicados
     */
    public function exportClients(Request $request, ExportService $exportService): StreamedResponse
    {
        try {
            // Aplicar los mismos filtros que getClients pero sin paginación
            $query = $this->buildFilteredQuery($request);
            $clients = $query->orderBy('created_at', 'desc')->get();

            // Si no hay clientes, exportar un archivo vacío con encabezados
            if ($clients->isEmpty()) {
                $data = [];
            } else {
                // Preparar datos para exportación
                $data = $clients->map(function($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name ?? '',
                        'last_name' => $client->last_name ?? '',
                        'email' => $client->email ?? '',
                        'phone' => $client->phone ?? '',
                        'status' => $client->status ? 'Activo' : 'Inactivo',
                        'has_account' => $client->has_account ? 'Sí' : 'No',
                        'country' => $client->country ?? '',
                        'created_at' => $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : '',
                    ];
                })->toArray();
            }

            // Definir encabezados
            $headers = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'last_name', 'label' => 'Apellido'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'phone', 'label' => 'Teléfono'],
                ['key' => 'status', 'label' => 'Estado'],
                ['key' => 'has_account', 'label' => 'Tiene Cuenta'],
                ['key' => 'country', 'label' => 'País'],
                ['key' => 'created_at', 'label' => 'Fecha de Creación'],
            ];

            // Generar nombre de archivo con fecha
            $filename = 'clientes_' . date('Y-m-d_His');

            return $exportService->exportToExcel($data, $headers, $filename);

        } catch (\Exception $e) {
            \Log::error('Error al exportar clientes: ' . $e->getMessage());
            abort(500, 'Error al exportar los clientes: ' . $e->getMessage());
        }
    }
}
