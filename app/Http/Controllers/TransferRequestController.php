<?php

namespace App\Http\Controllers;

use App\Models\AlfredAccount;
use App\Models\Client;
use App\Models\TransferRequest;
use App\Services\WasapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransferRequestController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/alfred/transfer-requests",
     *     summary="Crear solicitud de transferencia",
     *     description="Registra una solicitud de transferencia entre sender y receiver por teléfono. Estado inicial: solicitada.",
     *     operationId="alfredCreateTransferRequest",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sender_phone", "receiver_phone", "amount"},
     *             @OA\Property(property="sender_phone", type="string", example="18093901572", description="Teléfono del remitente"),
     *             @OA\Property(property="receiver_phone", type="string", example="18294428902", description="Teléfono del destinatario"),
     *             @OA\Property(property="amount", type="number", format="float", example=150.00, description="Monto solicitado en USD"),
     *             @OA\Property(property="currency", type="string", example="USD", description="Moneda (default USD)"),
     *             @OA\Property(property="notes", type="string", example="Transferencia familiar", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Solicitud creada",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="whatsapp_sent", type="boolean", example=true, description="Plantilla Solicitud Remesas enviada al sender"),
     *             @OA\Property(
     *                 property="transfer_request",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="uuid", type="string", format="uuid"),
     *                 @OA\Property(property="sender_phone", type="string", example="18093901572"),
     *                 @OA\Property(property="receiver_phone", type="string", example="18294428902"),
     *                 @OA\Property(property="amount", type="string", example="150.00"),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     enum={"solicitada", "rechazada", "aprobado", "completada", "cancelada"},
     *                     example="solicitada"
     *                 ),
     *                 @OA\Property(property="sender_customer_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(property="receiver_customer_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $req, WasapiService $wasapi)
    {
        try {
            $data = $req->validate([
                'sender_phone'   => 'required|string|min:7',
                'receiver_phone' => 'required|string|min:7|different:sender_phone',
                'amount'         => 'required|numeric|min:0.01|max:1000000',
                'currency'       => 'nullable|string|max:8',
                'notes'          => 'nullable|string|max:2000',
            ]);

            $senderPhone   = TransferRequest::normalizePhone((string) $data['sender_phone']);
            $receiverPhone = TransferRequest::normalizePhone((string) $data['receiver_phone']);

            if ($senderPhone === '' || $receiverPhone === '') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Teléfono del remitente o destinatario inválido.',
                ], 422);
            }

            if ($senderPhone === $receiverPhone) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'El remitente y el destinatario deben ser teléfonos distintos.',
                ], 422);
            }

            $senderCustomerId   = $this->resolveCustomerIdByPhone($senderPhone);
            $receiverCustomerId = $this->resolveCustomerIdByPhone($receiverPhone);

            $transferRequest = TransferRequest::create([
                'sender_phone'         => $senderPhone,
                'receiver_phone'       => $receiverPhone,
                'amount'               => $data['amount'],
                'currency'             => strtoupper((string) ($data['currency'] ?? 'USD')),
                'status'               => TransferRequest::STATUS_SOLICITADA,
                'sender_customer_id'   => $senderCustomerId,
                'receiver_customer_id' => $receiverCustomerId,
                'notes'                => $data['notes'] ?? null,
            ]);

            Log::info('Transfer request created', [
                'uuid'           => $transferRequest->uuid,
                'sender_phone'   => $senderPhone,
                'receiver_phone' => $receiverPhone,
                'amount'         => $transferRequest->formattedAmount(),
            ]);

            $receiverName = $this->resolveDisplayNameByPhone($receiverPhone);
            $whatsapp = $wasapi->notifyTransferRequestToSender(
                $senderPhone,
                $receiverName,
                $data['amount']
            );

            return response()->json([
                'ok'               => true,
                'whatsapp_sent'    => $whatsapp['sent'],
                'whatsapp_error'   => $whatsapp['error'],
                'transfer_request' => $transferRequest->fresh(),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('TransferRequest store error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo crear la solicitud de transferencia.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/alfred/transfer-requests/by-sender",
     *     summary="Consultar solicitudes de transferencia por teléfono del remitente",
     *     description="Devuelve las solicitudes activas (solicitada, aprobada) donde el sender debe enviar. El solicitante es el receiver de cada registro.",
     *     operationId="alfredTransferRequestsBySender",
     *     tags={"Alfred"},
     *     @OA\Parameter(
     *         name="sender_phone",
     *         in="query",
     *         required=true,
     *         description="Teléfono del remitente (sender)",
     *         @OA\Schema(type="string", example="18093901572")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consulta exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="sender_phone", type="string", example="18093901572"),
     *             @OA\Property(property="has_requests", type="boolean", example=true),
     *             @OA\Property(property="count", type="integer", example=1),
     *             @OA\Property(
     *                 property="requests",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="uuid", type="string", format="uuid"),
     *                     @OA\Property(property="requester_name", type="string", example="Dariel Abreu"),
     *                     @OA\Property(property="requester_phone", type="string", example="18294428902"),
     *                     @OA\Property(property="amount", type="string", example="150.00"),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="status", type="string", example="solicitada"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validación")
     * )
     */
    public function indexBySender(Request $req)
    {
        try {
            $data = $req->validate([
                'sender_phone' => 'required|string|min:7',
            ]);

            $senderPhone = TransferRequest::normalizePhone((string) $data['sender_phone']);
            if ($senderPhone === '') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Teléfono del remitente inválido.',
                ], 422);
            }

            $rows = TransferRequest::query()
                ->where('sender_phone', $senderPhone)
                ->whereIn('status', TransferRequest::ACTIVE_STATUSES)
                ->orderByDesc('created_at')
                ->get();

            $requests = $rows->map(function (TransferRequest $row) {
                return [
                    'id'              => $row->id,
                    'uuid'            => $row->uuid,
                    'requester_name'  => $this->resolveDisplayNameByPhone($row->receiver_phone, $row->receiver_customer_id),
                    'requester_phone' => $row->receiver_phone,
                    'amount'          => $row->formattedAmount(),
                    'currency'        => $row->currency,
                    'status'          => $row->status,
                    'created_at'      => $row->created_at?->toIso8601String(),
                ];
            })->values()->all();

            return response()->json([
                'ok'           => true,
                'sender_phone' => $senderPhone,
                'has_requests' => count($requests) > 0,
                'count'        => count($requests),
                'requests'     => $requests,
                'first-amount'       => $requests[0]['amount'],
                'first-currency'     => $requests[0]['currency'],
                'first-status'       => $requests[0]['status'],
                'first-uuid'         => $requests[0]['uuid'],
                'first-requester_name'         => $requests[0]['requester_name'],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('TransferRequest indexBySender error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudieron consultar las solicitudes.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/alfred/transfer-requests/cancel",
     *     summary="Cancelar solicitud de transferencia por sender y UUID",
     *     description="Busca la solicitud por UUID y teléfono del remitente (sender) y la marca como cancelada.",
     *     operationId="alfredCancelTransferRequest",
     *     tags={"Alfred"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sender_phone", "uuid"},
     *             @OA\Property(property="sender_phone", type="string", example="18093901572"),
     *             @OA\Property(property="uuid", type="string", format="uuid", example="a1b2c3d4-e5f6-7890-abcd-ef1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solicitud cancelada",
     *         @OA\JsonContent(
     *             @OA\Property(property="ok", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solicitud cancelada correctamente."),
     *             @OA\Property(property="whatsapp_sent", type="boolean", example=true, description="Plantilla Solicitud Cancelada enviada al receiver"),
     *             @OA\Property(property="whatsapp_error", type="string", nullable=true),
     *             @OA\Property(property="transfer_request", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Solicitud no encontrada"),
     *     @OA\Response(response=422, description="No se puede cancelar o validación")
     * )
     */
    public function cancelBySender(Request $req, WasapiService $wasapi)
    {
        try {
            $data = $req->validate([
                'sender_phone' => 'required|string|min:7',
                'uuid'         => 'required|string|uuid',
            ]);

            $senderPhone = TransferRequest::normalizePhone((string) $data['sender_phone']);
            if ($senderPhone === '') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Teléfono del remitente inválido.',
                ], 422);
            }

            $transferRequest = TransferRequest::query()
                ->where('uuid', $data['uuid'])
                ->where('sender_phone', $senderPhone)
                ->first();

            if ($transferRequest === null) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'No se encontró una solicitud con ese UUID para el teléfono indicado.',
                ], 404);
            }

            if (! $transferRequest->canBeCancelled()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Esta solicitud no puede cancelarse en su estado actual.',
                    'status'  => $transferRequest->status,
                ], 422);
            }

            $transferRequest->update(['status' => TransferRequest::STATUS_RECHAZADA]);

            Log::info('Transfer request cancelled via API', [
                'uuid'         => $transferRequest->uuid,
                'sender_phone' => $senderPhone,
            ]);

            $receiverName = $this->resolveDisplayNameByPhone($transferRequest->receiver_phone);
            $senderName = $this->resolveDisplayNameByPhone($transferRequest->sender_phone);
            $whatsapp = $wasapi->notifyTransferRequestCancelledToReceiver(
                $transferRequest->receiver_phone,
                $receiverName,
                $senderName,
                $transferRequest->amount
            );

            return response()->json([
                'ok'               => true,
                'message'          => 'Solicitud cancelada correctamente.',
                'whatsapp_sent'    => $whatsapp['sent'],
                'whatsapp_error'   => $whatsapp['error'],
                'transfer_request' => $transferRequest->fresh(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('TransferRequest cancelBySender error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo cancelar la solicitud.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveCustomerIdByPhone(string $phone): ?string
    {
        $client = $this->findClientByPhone($phone);
        $alfredAccount = $client?->alfredAccount;

        if ($alfredAccount === null) {
            $alfredAccount = $this->findAlfredAccountByPhone($phone);
        }

        if ($alfredAccount === null || empty($alfredAccount->alfred_customer_id)) {
            return null;
        }

        return (string) $alfredAccount->alfred_customer_id;
    }

    private function resolveDisplayNameByPhone(string $phone, ?string $alfredCustomerId = null): string
    {
        if ($alfredCustomerId !== null && trim($alfredCustomerId) !== '') {
            $name = $this->resolveDisplayNameByAlfredCustomerId(trim($alfredCustomerId));
            if ($name !== null) {
                return $name;
            }
        }

        $client = $this->findClientByPhone($phone);
        if ($client !== null) {
            $name = $this->extractClientDisplayName($client);
            if ($name !== null) {
                return $name;
            }

            $name = $this->extractAlfredAccountDisplayName($client->alfredAccount);
            if ($name !== null) {
                return $name;
            }
        }

        $alfredAccount = $this->findAlfredAccountByPhone($phone);
        if ($alfredAccount !== null) {
            $name = $this->extractAlfredAccountDisplayName($alfredAccount);
            if ($name !== null) {
                return $name;
            }

            $name = $this->extractClientDisplayName($alfredAccount->client);
            if ($name !== null) {
                return $name;
            }
        }

        $normalizedPhone = TransferRequest::normalizePhone($phone);

        return $normalizedPhone !== '' ? $normalizedPhone : $phone;
    }

    private function resolveDisplayNameByAlfredCustomerId(string $alfredCustomerId): ?string
    {
        $alfredAccount = AlfredAccount::query()
            ->where('alfred_customer_id', $alfredCustomerId)
            ->first();

        if ($alfredAccount === null) {
            return null;
        }

        $name = $this->extractAlfredAccountDisplayName($alfredAccount);
        if ($name !== null) {
            return $name;
        }

        return $this->extractClientDisplayName($alfredAccount->client);
    }

    private function findClientByPhone(string $phone): ?Client
    {
        return $this->applyPhoneMatch(Client::query()->with('alfredAccount'), 'phone', $phone)->first();
    }

    private function findAlfredAccountByPhone(string $phone): ?AlfredAccount
    {
        return $this->applyPhoneMatch(AlfredAccount::query()->with('client'), 'phone', $phone)->first();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function applyPhoneMatch($query, string $column, string $phone)
    {
        $normalized = TransferRequest::normalizePhone($phone);
        if ($normalized === '') {
            return $query->whereRaw('0 = 1');
        }

        $withoutLeadingOne = ltrim($normalized, '1');
        $digitsOnly = $this->sqlPhoneDigitsExpression($column);
        $variants = array_values(array_unique([
            $normalized,
            $withoutLeadingOne,
            '1'.$withoutLeadingOne,
        ]));

        return $query->where(function ($inner) use ($column, $variants, $digitsOnly, $normalized, $withoutLeadingOne) {
            $inner->whereIn($column, $variants)
                ->orWhereRaw("{$digitsOnly} = ?", [$normalized])
                ->orWhereRaw("{$digitsOnly} = ?", [$withoutLeadingOne])
                ->orWhereRaw("{$digitsOnly} = ?", ['1'.$withoutLeadingOne]);
        });
    }

    /** Expresión SQL compatible con MySQL 5.7 / MariaDB (sin REGEXP_REPLACE). */
    private function sqlPhoneDigitsExpression(string $column): string
    {
        $expression = $column;

        foreach (['+', '-', ' ', '(', ')', '.'] as $char) {
            $expression = "REPLACE({$expression}, '{$char}', '')";
        }

        return $expression;
    }

    private function extractClientDisplayName(?Client $client): ?string
    {
        if ($client === null) {
            return null;
        }

        $name = trim(((string) ($client->name ?? '')).' '.((string) ($client->last_name ?? '')));

        return $name !== '' ? $name : null;
    }

    private function extractAlfredAccountDisplayName(?AlfredAccount $alfredAccount): ?string
    {
        if ($alfredAccount === null) {
            return null;
        }

        $name = trim(((string) ($alfredAccount->first_name ?? '')).' '.((string) ($alfredAccount->last_name ?? '')));
        if ($name !== '') {
            return $name;
        }

        if (! empty($alfredAccount->full_name)) {
            $fullName = trim((string) $alfredAccount->full_name);

            return $fullName !== '' ? $fullName : null;
        }

        return null;
    }
}
