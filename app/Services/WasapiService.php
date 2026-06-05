<?php

namespace App\Services;

use App\Models\WasapiSetting;
use App\Models\WasapiWhatsappLine;
use App\Models\WasapiWhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WasapiService
{
    public const CATEGORY_SOLICITUD_REMESA = 'Solicitud Remesas';

    /** @var list<string> */
    private const CATEGORY_SOLICITUD_REMESA_ALIASES = [
        'Solicitud Remesas',
        'Solicitud de remesa',
    ];
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchWhatsAppTemplates(?string $apiToken = null): array
    {
        $response = $this->request('GET', '/whatsapp-templates', [], $apiToken);
        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * @param  list<array<string, mixed>>  $remoteTemplates
     * @return array{saved: int, templates: \Illuminate\Database\Eloquent\Collection<int, WasapiWhatsappTemplate>}
     */
    public function saveSelectedTemplates(array $remoteTemplates, array $selectedWasapiIds): array
    {
        $selectedWasapiIds = array_values(array_unique(array_map('intval', $selectedWasapiIds)));

        if ($selectedWasapiIds === []) {
            WasapiWhatsappTemplate::query()->delete();

            return [
                'saved'     => 0,
                'templates' => WasapiWhatsappTemplate::query()->orderBy('template_id')->get(),
            ];
        }

        $byId = [];
        foreach ($remoteTemplates as $template) {
            if (! is_array($template) || empty($template['id'])) {
                continue;
            }
            $byId[(int) $template['id']] = $template;
        }

        $savedCount = 0;

        foreach ($selectedWasapiIds as $wasapiId) {
            $template = $byId[$wasapiId] ?? null;
            if ($template === null) {
                continue;
            }

            WasapiWhatsappTemplate::updateOrCreate(
                ['wasapi_id' => $wasapiId],
                [
                    'uuid'        => (string) ($template['uuid'] ?? ''),
                    'template_id' => (string) ($template['template_id'] ?? ''),
                    'status'      => (string) ($template['status'] ?? ''),
                ]
            );
            $savedCount++;
        }

        WasapiWhatsappTemplate::query()
            ->whereNotIn('wasapi_id', $selectedWasapiIds)
            ->delete();

        return [
            'saved'     => $savedCount,
            'templates' => WasapiWhatsappTemplate::query()->orderBy('template_id')->get(),
        ];
    }

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
     * Envía una plantilla de WhatsApp aprobada (POST /whatsapp-messages/send-template).
     *
     * @link https://api-docs.wasapi.io/reference/sendwhatsapptemplate
     *
     * @param  array{
     *     template_id: string,
     *     recipients: string|array<int, string>,
     *     contact_type?: 'phone'|'contact',
     *     from_id?: int|null,
     *     body_vars?: array<int, mixed>|list<array{text: string, val: string}>,
     *     header_var?: array<int, mixed>|list<array{text: string, val: string}>,
     *     cta_var?: array<int, mixed>|list<array{text: string, val: string}>,
     *     file?: 'document'|'image'|'video'|'audio'|null,
     *     url_file?: string|null,
     *     file_name?: string|null,
     *     chatbot_status?: 'enable'|'disable'|'disable_permanently'|null,
     *     conversation_status?: 'open'|'hold'|'closed'|'unchanged'|null,
     * }  $params
     * @return array<string, mixed>
     */
    public function sendWhatsAppTemplate(array $params, ?string $apiToken = null): array
    {
        $templateUuid = trim((string) ($params['template_id'] ?? ''));
        if ($templateUuid === '') {
            throw new \InvalidArgumentException('template_id (UUID de Wasapi) es requerido.');
        }

        $recipients = $this->normalizeTemplateRecipients($params['recipients'] ?? '');
        if ($recipients === '') {
            throw new \InvalidArgumentException('recipients es requerido (teléfono(s) o IDs de contacto).');
        }

        $contactType = strtolower(trim((string) ($params['contact_type'] ?? 'phone')));
        if (! in_array($contactType, ['phone', 'contact'], true)) {
            throw new \InvalidArgumentException('contact_type debe ser phone o contact.');
        }

        $fromId = $this->resolveFromId(isset($params['from_id']) ? (int) $params['from_id'] : null);
        if ($fromId === null) {
            throw new \RuntimeException('Wasapi no tiene línea por defecto configurada (from_id).');
        }

        $payload = [
            'template_id'  => $templateUuid,
            'recipients'   => $recipients,
            'contact_type' => $contactType,
            'from_id'      => $fromId,
        ];

        if (isset($params['body_vars']) && is_array($params['body_vars']) && $params['body_vars'] !== []) {
            $payload['body_vars'] = $this->normalizeTemplateVars($params['body_vars']);
        }

        if (isset($params['header_var']) && is_array($params['header_var']) && $params['header_var'] !== []) {
            $payload['header_var'] = $this->normalizeTemplateVars($params['header_var']);
        }

        if (isset($params['cta_var']) && is_array($params['cta_var']) && $params['cta_var'] !== []) {
            $payload['cta_var'] = $this->normalizeTemplateVars($params['cta_var']);
        }

        foreach (['file', 'url_file', 'file_name', 'chatbot_status', 'conversation_status'] as $key) {
            if (! empty($params[$key])) {
                $payload[$key] = $params[$key];
            }
        }

        if (! empty($payload['file'])) {
            $allowedFiles = ['document', 'image', 'video', 'audio'];
            if (! in_array($payload['file'], $allowedFiles, true)) {
                throw new \InvalidArgumentException('file debe ser document, image, video o audio.');
            }
            if (empty($payload['url_file'])) {
                throw new \InvalidArgumentException('url_file es requerido cuando se adjunta file en el encabezado.');
            }
        }

        Log::info('Wasapi POST /whatsapp-messages/send-template', [
            'template_id'  => $templateUuid,
            'recipients'   => $recipients,
            'contact_type' => $contactType,
            'from_id'      => $fromId,
            'body_vars'    => $payload['body_vars'] ?? null,
            'header_var'   => $payload['header_var'] ?? null,
            'cta_var'      => $payload['cta_var'] ?? null,
            'file'         => $payload['file'] ?? null,
        ]);

        $response = $this->request('POST', '/whatsapp-messages/send-template', $payload, $apiToken);

        Log::info('Wasapi POST /whatsapp-messages/send-template response', [
            'template_id' => $templateUuid,
            'recipients'  => $recipients,
            'response'    => $response,
        ]);

        return $response;
    }

    /**
     * Envía plantilla usando el UUID guardado en una plantilla local por nombre de categoría.
     *
     * @param  string|array<int, string>  $recipients
     * @param  array{
     *     body_vars?: array<int, mixed>|list<array{text: string, val: string}>,
     *     header_var?: array<int, mixed>|list<array{text: string, val: string}>,
     *     cta_var?: array<int, mixed>|list<array{text: string, val: string}>,
     *     file?: string|null,
     *     url_file?: string|null,
     *     file_name?: string|null,
     *     contact_type?: string,
     *     from_id?: int|null,
     *     chatbot_status?: string|null,
     *     conversation_status?: string|null,
     * }  $options
     * @return array<string, mixed>
     */
    public function sendWhatsAppTemplateByCategory(
        string $categoryName,
        string|array $recipients,
        array $options = [],
        ?string $apiToken = null
    ): array {
        $template = $this->resolveTemplateByCategoryName($categoryName);

        if ($template === null || trim((string) $template->uuid) === '') {
            throw new \RuntimeException("No hay plantilla guardada con UUID para la categoría «{$categoryName}».");
        }

        return $this->sendWhatsAppTemplate(array_merge($options, [
            'template_id' => $template->uuid,
            'recipients'  => $recipients,
        ]), $apiToken);
    }

    /**
     * Notifica al sender que el receiver le solicitó una remesa (plantilla «Solicitud Remesas»).
     *
     * @return array{sent: bool, error: string|null}
     */
    public function notifyTransferRequestToSender(
        string $senderPhone,
        string $receiverName,
        float|string $amount
    ): array {
        $result = ['sent' => false, 'error' => null];

        if (! $this->isConfigured()) {
            Log::debug('Wasapi notifyTransferRequestToSender: omitido (sin configuración).');

            return $result;
        }

        $receiverName = trim($receiverName);
        if ($receiverName === '') {
            $receiverName = 'Usuario';
        }

        $amountFormatted = number_format((float) $amount, 2, '.', '');

        try {
            $response = $this->sendWhatsAppTemplateByCategory(
                self::CATEGORY_SOLICITUD_REMESA,
                $senderPhone,
                [
                    'contact_type' => 'phone',
                    'body_vars'    => [$receiverName, $amountFormatted],
                ]
            );
            $result['sent'] = true;
            Log::info('Wasapi notifyTransferRequestToSender response', [
                'sender_phone'  => $senderPhone,
                'receiver_name' => $receiverName,
                'amount'        => $amountFormatted,
                'response'      => $response,
            ]);
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            Log::warning('Wasapi notifyTransferRequestToSender falló', [
                'sender_phone'  => $senderPhone,
                'receiver_name' => $receiverName,
                'amount'        => $amountFormatted,
                'message'       => $e->getMessage(),
            ]);
        }

        return $result;
    }

    private function resolveTemplateByCategoryName(string $categoryName): ?WasapiWhatsappTemplate
    {
        $names = array_values(array_unique([$categoryName, ...self::CATEGORY_SOLICITUD_REMESA_ALIASES]));

        foreach ($names as $name) {
            $template = WasapiWhatsappTemplate::findByCategoryName($name);
            if ($template !== null) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @param  string|array<int, string>  $recipients
     */
    private function normalizeTemplateRecipients(string|array $recipients): string
    {
        $list = is_array($recipients) ? $recipients : explode(',', (string) $recipients);
        $normalized = [];

        foreach ($list as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $normalized[] = $this->normalizeWaDigits($item) ?: $item;
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            return '';
        }

        if (count($normalized) > 20) {
            throw new \InvalidArgumentException('Wasapi permite máximo 20 destinatarios por envío de plantilla.');
        }

        return implode(',', $normalized);
    }

    /**
     * Acepta variables en formato Wasapi [{text, val}] o lista simple de valores ({{1}}, {{2}}, …).
     *
     * @param  array<int, mixed>  $vars
     * @return list<array{text: string, val: string}>
     */
    private function normalizeTemplateVars(array $vars): array
    {
        $out = [];

        foreach ($vars as $key => $value) {
            if (is_array($value) && isset($value['text'], $value['val'])) {
                $out[] = [
                    'text' => (string) $value['text'],
                    'val'  => (string) $value['val'],
                ];
                continue;
            }

            if (is_string($key) && str_contains($key, '{{')) {
                $out[] = ['text' => $key, 'val' => (string) $value];
                continue;
            }

            if (is_int($key) || (is_string($key) && ctype_digit($key))) {
                $index = (int) $key;
                $placeholder = '{{'.($index + 1).'}}';
                $out[] = ['text' => $placeholder, 'val' => (string) $value];
            }
        }

        return $out;
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

        $json = $response->json() ?? [];
        $this->logWasapiResponse($method, $path, $response->status(), $json);

        return $json;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function logWasapiResponse(string $method, string $path, int $status, array $json): void
    {
        if (strtoupper($method) === 'GET' && isset($json['data']) && is_array($json['data'])) {
            Log::info('Wasapi HTTP response', [
                'method'     => $method,
                'path'       => $path,
                'status'     => $status,
                'success'    => $json['success'] ?? null,
                'data_count' => count($json['data']),
            ]);

            return;
        }

        Log::info('Wasapi HTTP response', [
            'method'   => $method,
            'path'     => $path,
            'status'   => $status,
            'response' => $json,
        ]);
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
