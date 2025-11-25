<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\WaitingList;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
class WaitingListController extends Controller
{
    public function getWaitingList(Request $request): JsonResponse
    {
        try {
            // Usar el método compartido para construir la query con filtros
            $query = $this->buildFilteredQuery($request);

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

    /**
     * Construye la query con los filtros aplicados
     */
    private function buildFilteredQuery(Request $request)
    {
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

        return $query;
    }

    /**
     * Exportar lista de espera a Excel con los mismos filtros aplicados
     */
    public function exportWaitingList(Request $request, ExportService $exportService): StreamedResponse
    {
        try {
            // Aplicar los mismos filtros que getWaitingList pero sin paginación
            $query = $this->buildFilteredQuery($request);
            $waitingList = $query->orderBy('created_at', 'desc')->get();

            // Si no hay registros, exportar un archivo vacío con encabezados
            if ($waitingList->isEmpty()) {
                $data = [];
            } else {
                // Preparar datos para exportación
                $data = $waitingList->map(function($item) {
                    $client = $item->client ?? (object)[];
                    return [
                        'id' => $item->id,
                        'client_name' => $client->name ?? '',
                        'client_last_name' => $client->last_name ?? '',
                        'client_email' => $client->email ?? '',
                        'client_phone' => $client->phone ?? '',
                        'client_status' => isset($client->status) && $client->status ? 'Activo' : 'Inactivo',
                        'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '',
                    ];
                })->toArray();
            }

            // Definir encabezados
            $headers = [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'client_name', 'label' => 'Nombre'],
                ['key' => 'client_last_name', 'label' => 'Apellido'],
                ['key' => 'client_email', 'label' => 'Email'],
                ['key' => 'client_phone', 'label' => 'Teléfono'],
                ['key' => 'client_status', 'label' => 'Estado'],
                ['key' => 'created_at', 'label' => 'Fecha de Registro'],
            ];

            // Generar nombre de archivo con fecha
            $filename = 'lista_espera_' . date('Y-m-d_His');

            return $exportService->exportToExcel($data, $headers, $filename);

        } catch (\Exception $e) {
            Log::error('Error al exportar lista de espera: ' . $e->getMessage());
            abort(500, 'Error al exportar la lista de espera: ' . $e->getMessage());
        }
    }
}
