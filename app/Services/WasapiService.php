<?php

namespace App\Services;

use App\Models\WasapiSetting;
use App\Models\WasapiWhatsappLine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WasapiService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchWhatsAppNumbers(?string $apiToken = null): array
    {
        $response = $this->request('GET', '/whatsapp-numbers', [], $apiToken);
        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * Sincroniza líneas desde Wasapi a la base de datos local.
     *
     * @return array{synced: int, lines: \Illuminate\Database\Eloquent\Collection<int, WasapiWhatsappLine>}
     */
    public function syncWhatsappLines(?string $apiToken = null): array
    {
        $remoteLines = $this->fetchWhatsAppNumbers($apiToken);
        $syncedIds = [];

        foreach ($remoteLines as $line) {
            if (! is_array($line) || empty($line['id'])) {
                continue;
            }

            $wasapiId = (int) $line['id'];
            $syncedIds[] = $wasapiId;

            $app = is_array($line['app'] ?? null) ? $line['app'] : [];
            $phoneNumber = (string) ($line['phone_number'] ?? '');

            WasapiWhatsappLine::updateOrCreate(
                ['wasapi_id' => $wasapiId],
                [
                    'uuid'              => $line['uuid'] ?? null,
                    'user_id'           => isset($line['user_id']) ? (int) $line['user_id'] : null,
                    'app_id'            => isset($line['app_id']) ? (int) $line['app_id'] : null,
                    'display_name'      => (string) ($line['display_name'] ?? 'Sin nombre'),
                    'phone_number'      => $phoneNumber,
                    'phone_digits'      => $this->normalizeWaDigits($phoneNumber),
                    'phone_id'          => isset($line['phone_id']) ? (string) $line['phone_id'] : null,
                    'quality_score'     => $line['quality_score'] ?? null,
                    'can_send_message'  => $line['can_send_message'] ?? null,
                    'app_name'          => $app['name'] ?? null,
                    'waba_id'           => $app['waba_id'] ?? null,
                    'extra'             => $line,
                ]
            );
        }

        if ($syncedIds !== []) {
            WasapiWhatsappLine::query()
                ->whereNotIn('wasapi_id', $syncedIds)
                ->delete();
        }

        $lines = WasapiWhatsappLine::query()->orderBy('display_name')->get();

        if ($lines->count() === 1 && ! $lines->first()->is_default) {
            WasapiWhatsappLine::setDefault((int) $lines->first()->id);
            $lines = WasapiWhatsappLine::query()->orderBy('display_name')->get();
        }

        if ($lines->where('is_default', true)->isEmpty() && $lines->isNotEmpty()) {
            WasapiWhatsappLine::setDefault((int) $lines->first()->id);
            $lines = WasapiWhatsappLine::query()->orderBy('display_name')->get();
        }

        return [
            'synced' => count($syncedIds),
            'lines'  => $lines,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->resolveApiToken() !== null && $this->resolveFromId(null) !== null;
    }

    /**
     * Envía un mensaje de texto por WhatsApp (POST /whatsapp-messages).
     *
     * @param  string  $waDigits  Número en dígitos, sin "+"
     * @param  int|null  $fromId  wasapi_id de la línea; si es null usa la línea por defecto
     * @return array<string, mixed>
     */
    public function sendTextMessage(string $waDigits, string $message, ?int $fromId = null, ?string $apiToken = null): array
    {
        $token = $this->resolveApiToken($apiToken);
        if ($token === null) {
            throw new \RuntimeException('Wasapi no está configurado: falta el token API.');
        }

        $fromId = $this->resolveFromId($fromId);
        if ($fromId === null) {
            throw new \RuntimeException('Wasapi no tiene línea por defecto configurada (from_id).');
        }

        $waDigits = $this->normalizeWaDigits($waDigits);
        if ($waDigits === '') {
            throw new \InvalidArgumentException('wa_id vacío: no hay número de teléfono válido.');
        }

        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('El mensaje no puede estar vacío.');
        }

        return $this->request('POST', '/whatsapp-messages', [
            'from_id' => $fromId,
            'wa_id'   => $waDigits,
            'message' => $message,
        ], $token);
    }

    /**
     * @return bool true si se envió el mensaje; false si no hay config o falló el envío
     */
    public function notifyKycRegistrationComplete(string $phoneDigits, ?string $firstName = null): bool
    {
        if (! $this->isConfigured()) {
            Log::debug('Wasapi: omitido (sin configuración).');

            return false;
        }

        $greeting = $firstName ? "Hola, {$firstName}. " : 'Hola. ';
        $text = $greeting.'Tu proceso de registro se completó de manera correcta. La verificación de identidad (KYC) fue enviada con éxito. ¡Gracias!';

        try {
            $this->sendTextMessage($phoneDigits, $text);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Wasapi notifyKycRegistrationComplete falló', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Notifica por WhatsApp al sender y receiver cuando la orden se completa.
     *
     * @return array{sender_sent: bool, receiver_sent: bool, sender_phone: string|null, receiver_phone: string|null}
     */
    public function notifyOrderTransactionComplete(?string $senderPhone, ?string $receiverPhone): array
    {
        $result = [
            'sender_sent'    => false,
            'receiver_sent'  => false,
            'sender_phone'   => $senderPhone,
            'receiver_phone' => $receiverPhone,
        ];

        if (! $this->isConfigured()) {
            Log::debug('Wasapi notifyOrderTransactionComplete: omitido (sin configuración).');

            return $result;
        }

        $senderMessage = 'Completaste tu transaccion. gracias por utilizar el servicio de Domipago';
        $receiverMessage = 'Se completo el envio, vas a recibir el dinero en la cuenta que registraste en las proximas horas.gracias por utilizar el servicio de Domipago';

        if ($senderPhone !== null && $senderPhone !== '') {
            try {
                $this->sendTextMessage($senderPhone, $senderMessage);
                $result['sender_sent'] = true;
            } catch (\Throwable $e) {
                Log::warning('Wasapi notifyOrderTransactionComplete sender falló', [
                    'phone'   => $senderPhone,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($receiverPhone !== null && $receiverPhone !== '' && $receiverPhone !== $senderPhone) {
            try {
                $this->sendTextMessage($receiverPhone, $receiverMessage);
                $result['receiver_sent'] = true;
            } catch (\Throwable $e) {
                Log::warning('Wasapi notifyOrderTransactionComplete receiver falló', [
                    'phone'   => $receiverPhone,
                    'message' => $e->getMessage(),
                ]);
            }
        } elseif ($receiverPhone !== null && $receiverPhone !== '' && $receiverPhone === $senderPhone) {
            try {
                $this->sendTextMessage($receiverPhone, $receiverMessage);
                $result['receiver_sent'] = true;
            } catch (\Throwable $e) {
                Log::warning('Wasapi notifyOrderTransactionComplete receiver (mismo teléfono) falló', [
                    'phone'   => $receiverPhone,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = [], ?string $apiToken = null): array
    {
        $token = $this->resolveApiToken($apiToken);
        if ($token === null) {
            throw new \RuntimeException('Token de Wasapi no configurado.');
        }

        $base = rtrim($this->resolveBaseUri(), '/');
        $url = $base . '/' . ltrim($path, '/');

        $http = Http::withToken($token)->acceptJson()->timeout(30);

        $response = match (strtoupper($method)) {
            'GET'    => $http->get($url, $payload),
            'POST'   => $http->post($url, $payload),
            'PUT'    => $http->put($url, $payload),
            'DELETE' => $http->delete($url, $payload),
            default  => throw new \InvalidArgumentException("Método HTTP no soportado: {$method}"),
        };

        if (! $response->successful()) {
            Log::warning('Wasapi HTTP error', [
                'method' => $method,
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            $response->throw();
        }

        return $response->json() ?? [];
    }

    private function resolveApiToken(?string $override = null): ?string
    {
        $override = trim((string) ($override ?? ''));
        if ($override !== '') {
            return $override;
        }

        $dbToken = WasapiSetting::instance()->api_token;
        if ($dbToken !== null && trim($dbToken) !== '') {
            return trim($dbToken);
        }

        $envToken = trim((string) config('services.wasapi.api_key', ''));

        return $envToken !== '' ? $envToken : null;
    }

    private function resolveBaseUri(): string
    {
        $dbUri = trim((string) (WasapiSetting::instance()->base_uri ?? ''));
        if ($dbUri !== '') {
            return $dbUri;
        }

        return rtrim((string) config('services.wasapi.base_uri', 'https://api-ws.wasapi.io/api/v1'), '/');
    }

    private function resolveFromId(?int $fromId): ?int
    {
        if ($fromId !== null && $fromId > 0) {
            return $fromId;
        }

        $defaultLine = WasapiWhatsappLine::defaultLine();
        if ($defaultLine !== null) {
            return (int) $defaultLine->wasapi_id;
        }

        $envFromId = config('services.wasapi.from_id');
        if ($envFromId !== null && $envFromId !== '') {
            return (int) $envFromId;
        }

        return null;
    }

    private function normalizeWaDigits(string $input): string
    {
        return preg_replace('/\D/', '', $input) ?? '';
    }
}
