<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use RuntimeException;

class MailchimpMarketingService
{
    private string $baseUrl;
    private string $apiKey;
    private string $audienceId;

    public function __construct()
    {
        $server = (string) Config::get('services.mailchimp.server');
        $this->apiKey = (string) Config::get('services.mailchimp.api_key');
        $this->audienceId = (string) Config::get('services.mailchimp.audience_id');

        $this->baseUrl = "https://{$server}.api.mailchimp.com/3.0";
    }

    private function client()
    {
        // Mailchimp usa Basic Auth; el username puede ser cualquier string
        return Http::timeout(20)->withBasicAuth('anystring', $this->apiKey);
    }

    /**
     * Guardrail: este servicio NO permite envíos masivos.
     * Si alguien intenta agregar un send masivo, rompemos explícitamente.
     */
    private function guardAgainstBulkSend(string $endpoint): void
    {
        // Cualquier intento de usar actions/send es envío masivo.
        if (str_contains($endpoint, '/actions/send')) {
            throw new RuntimeException(
                'Operación bloqueada: este servicio está configurado para NO permitir envíos masivos. ' .
                'Use únicamente /actions/test para envíos individuales.'
            );
        }
    }

    private function normalizeEmail(string $email): string
    {
        $email = trim(mb_strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email inválido: {$email}");
        }
        return $email;
    }

    /**
     * 1) Crea una campaña tipo "regular" (requerido por Mailchimp),
     * pero NUNCA se enviará a la audiencia; solo se usará para test send.
     */
    public function createCampaignForIndividualOnly(string $subject): array
    {
        $payload = [
            'type' => 'regular',
            'recipients' => [
                // Mailchimp requiere list_id para campañas regulares
                'list_id' => $this->audienceId,
            ],
            'settings' => [
                'subject_line' => $subject,
                'from_name' => (string) Config::get('services.mailchimp.from_name'),
                'reply_to' => (string) Config::get('services.mailchimp.reply_to'),

                // Recomendado: deja evidencias en UI de que esto es SOLO INDIVIDUAL (test)
                'title' => '[INDIVIDUAL-ONLY] ' . $subject,
            ],
        ];

        $endpoint = $this->baseUrl . '/campaigns';
        $res = $this->client()->post($endpoint, $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        return $res->json();
    }

    /**
     * 2) Asigna el contenido a la campaña usando template_id.
     */
    public function setCampaignContentWithTemplate(string $campaignId, int $templateId, array $sections = []): array
    {
        $payload = [
            'template' => [
                'id' => $templateId,
                'sections' => (object) $sections,
            ],
        ];

        $endpoint = $this->baseUrl . "/campaigns/{$campaignId}/content";
        $res = $this->client()->put($endpoint, $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        return $res->json();
    }

    /**
     * Alternativa: asignar HTML directo si no quieres template_id.
     */
    public function setCampaignContentWithHtml(string $campaignId, string $html): array
    {
        $payload = ['html' => $html];

        $endpoint = $this->baseUrl . "/campaigns/{$campaignId}/content";
        $res = $this->client()->put($endpoint, $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        return $res->json();
    }

    /**
     * 3) Envío INDIVIDUAL: SOLO "test email" al correo especificado.
     * Este método es el ÚNICO permitido para envío.
     */
    public function sendIndividualTestEmail(string $campaignId, string $email): array
    {
        $email = $this->normalizeEmail($email);

        $payload = [
            'test_emails' => [$email],
            'send_type' => 'html',
        ];

        $endpoint = $this->baseUrl . "/campaigns/{$campaignId}/actions/test";
        $this->guardAgainstBulkSend($endpoint);

        $res = $this->client()->post($endpoint, $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        // Mailchimp suele devolver 204 No Content cuando todo sale bien
        return $res->json() ?? ['ok' => true];
    }

    /**
     * Flujo completo seguro:
     * create -> set content -> send test (individual)
     * NO existe send masivo en este servicio.
     */
    public function sendIndividualUsingTemplate(string $toEmail, string $subject, int $templateId, array $sections = []): array
    {
        $toEmail = $this->normalizeEmail($toEmail);
        $toEmail = $this->normalizeEmail($toEmail);

        $campaign = $this->createCampaignForIndividualOnly($subject);
        $campaignId = $campaign['id'] ?? null;
    
        if (!$campaignId) {
            return ['ok' => false, 'error' => 'No se pudo obtener campaign_id al crear la campaña.'];
        }
    
        $this->setCampaignContentWithTemplate($campaignId, $templateId, $sections);
        $this->sendIndividualTestEmail($campaignId, $toEmail);
    
        // Limpieza: eliminar draft para que no quede visible en Mailchimp
        $this->deleteCampaign($campaignId);
    
        return [
            'ok' => true,
            'sent_to' => $toEmail,
            'mode' => 'individual_test_only',
            'campaign_deleted' => true
        ];
    }

    /**
     * Variante: enviar individual con HTML directo.
     */
    public function sendIndividualUsingHtml(string $toEmail, string $subject, string $html): array
    {
        $toEmail = $this->normalizeEmail($toEmail);

        $campaign = $this->createCampaignForIndividualOnly($subject);
        $campaignId = $campaign['id'] ?? null;

        if (!$campaignId) {
            return ['ok' => false, 'error' => 'No se pudo obtener campaign_id al crear la campaña.'];
        }

        $this->setCampaignContentWithHtml($campaignId, $html);
        $this->sendIndividualTestEmail($campaignId, $toEmail);

        return [
            'ok' => true,
            'campaign_id' => $campaignId,
            'sent_to' => $toEmail,
            'mode' => 'individual_test_only',
        ];
    }

    public function deleteCampaign(string $campaignId): void
    {
        $endpoint = $this->baseUrl . "/campaigns/{$campaignId}";
        
        $res = $this->client()->delete($endpoint);
        
        if ($res->failed()) {
            throw new \Illuminate\Http\Client\RequestException($res);
        }
    }
    
    public function sendTransactionalTemplate(string $toEmail, string $subject, string $templateName): array
    {
        $payload = [
            'key' => Config::get('services.mailchimp_tx.api_key'),
            'template_name' => $templateName,
            'template_content' => [],
            'message' => [
                'subject' => $subject,
                'from_email' => Config::get('services.mailchimp_tx.from_email'),
                'from_name' => Config::get('services.mailchimp_tx.from_name'),
                'to' => [[ 'email' => $toEmail, 'type' => 'to' ]],
            ],
            'async' => false
        ];
        
        $res = \Illuminate\Support\Facades\Http::post(
            'https://mandrillapp.com/api/1.0/messages/send-template.json',
            $payload
        );
        
        if ($res->failed()) {
            $statusCode = $res->status();
            $responseBody = $res->json();
            
            // Validar error 401 - Invalid API key
            if ($statusCode === 401) {
                $errorMessage = $responseBody['message'] ?? 'Invalid API key';
                $errorCode = $responseBody['code'] ?? 401;
                $errorName = $responseBody['name'] ?? 'Invalid_Key';
                
                \Illuminate\Support\Facades\Log::error('Mailchimp Transactional API Error 401', [
                    'error_code' => $errorCode,
                    'error_name' => $errorName,
                    'error_message' => $errorMessage,
                    'email' => $toEmail,
                    'template_name' => $templateName,
                    'api_key_configured' => !empty(Config::get('services.mailchimp_tx.api_key')),
                ]);
                
                throw new \RuntimeException(
                    "Error de autenticación con Mailchimp Transactional API: {$errorMessage}. " .
                    "Por favor, verifica que la API key esté configurada correctamente en el archivo .env"
                );
            }
            
            // Para otros errores, lanzar la excepción original
            throw new \Illuminate\Http\Client\RequestException($res);
        }
        
        return $res->json();
    }
}
