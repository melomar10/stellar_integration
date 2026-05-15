<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WasapiService
{
    public function isConfigured(): bool
    {
        $key = (string) config('services.wasapi.api_key', '');
        $fromId = config('services.wasapi.from_id');

        return $key !== '' && $fromId !== null && $fromId !== '';
    }

    /**
     * Envía un mensaje de texto por WhatsApp (API Wasapi: POST /whatsapp-messages).
     *
     * @param  string  $waDigits  Número en dígitos, sin "+", p. ej. 18095551234 o 573001234567
     * @return array<string, mixed>  Cuerpo JSON decodificado de Wasapi
     */
    public function sendTextMessage(string $waDigits, string $message): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Wasapi no está configurado (WASAPI_API_KEY / WASAPI_FROM_ID).');
        }

        $base = rtrim((string) config('services.wasapi.base_uri', 'https://api-ws.wasapi.io/api/v1'), '/');
        $apiKey = (string) config('services.wasapi.api_key');
        $fromId = (int) config('services.wasapi.from_id');

        $waDigits = preg_replace('/\D/', '', $waDigits) ?? '';
        if ($waDigits === '') {
            throw new \InvalidArgumentException('wa_id vacío: no hay número de teléfono válido.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->post("{$base}/whatsapp-messages", [
                'from_id' => $fromId,
                'wa_id'   => $waDigits,
                'message' => $message,
            ]);

        if (!$response->successful()) {
            Log::warning('Wasapi sendTextMessage HTTP error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            $response->throw();
        }

        return $response->json() ?? [];
    }

    /**
     * @return bool true si se envió el mensaje; false si no hay config, número o falló el envío
     */
    public function notifyKycRegistrationComplete(string $phoneDigits, ?string $firstName = null): bool
    {
        if (!$this->isConfigured()) {
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
}
