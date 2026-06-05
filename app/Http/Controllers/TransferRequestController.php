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

    private function resolveCustomerIdByPhone(string $phone): ?string
    {
        $client = Client::where('phone', $phone)->with('alfredAccount')->first();
        $alfredAccount = $client?->alfredAccount;

        if (!$alfredAccount) {
            $alfredAccount = AlfredAccount::where('phone', $phone)->first();
        }

        if (!$alfredAccount || empty($alfredAccount->alfred_customer_id)) {
            return null;
        }

        return (string) $alfredAccount->alfred_customer_id;
    }

    private function resolveDisplayNameByPhone(string $phone): string
    {
        $client = Client::where('phone', $phone)->first();
        if ($client) {
            $name = trim(((string) ($client->name ?? '')).' '.((string) ($client->last_name ?? '')));
            if ($name !== '') {
                return $name;
            }
        }

        $alfredAccount = AlfredAccount::where('phone', $phone)->first();
        if ($alfredAccount) {
            $name = trim(((string) ($alfredAccount->first_name ?? '')).' '.((string) ($alfredAccount->last_name ?? '')));
            if ($name !== '') {
                return $name;
            }
            if (! empty($alfredAccount->full_name)) {
                return trim((string) $alfredAccount->full_name);
            }
        }

        return $phone;
    }
}
