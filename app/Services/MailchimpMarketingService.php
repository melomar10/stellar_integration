<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Client\RequestException;

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
     * 1) Crea una campaña tipo "regular".
     * Docs: POST /campaigns :contentReference[oaicite:1]{index=1}
     */
    public function createCampaign(string $subject): array
    {
        $payload = [
            'type' => 'regular',
            'recipients' => [
                'list_id' => $this->audienceId,
            ],
            'settings' => [
                'subject_line' => $subject,
                'from_name' => (string) Config::get('services.mailchimp.from_name'),
                'reply_to' => (string) Config::get('services.mailchimp.reply_to'),
            ],
        ];

        $res = $this->client()->post($this->baseUrl . '/campaigns', $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        return $res->json();
    }

    /**
     * 2) Asigna el contenido a la campaña usando template_id.
     * Docs: PUT /campaigns/{campaign_id}/content :contentReference[oaicite:2]{index=2}
     */
    public function setCampaignContentWithTemplate(string $campaignId, int $templateId, array $sections = []): array
    {
        $payload = [
            'template' => [
                'id' => $templateId,
                // Si tu template usa secciones editables, las mandas aquí:
                // 'sections' => ['body' => '<h1>...</h1>']
                'sections' => (object) $sections,
            ],
        ];

        $res = $this->client()->put($this->baseUrl . "/campaigns/{$campaignId}/content", $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        return $res->json();
    }

    /**
     * Alternativa: asignar HTML directo si no quieres template_id.
     * Docs: PUT /campaigns/{campaign_id}/content :contentReference[oaicite:3]{index=3}
     */
    public function setCampaignContentWithHtml(string $campaignId, string $html): array
    {
        $payload = ['html' => $html];

        $res = $this->client()->put($this->baseUrl . "/campaigns/{$campaignId}/content", $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        return $res->json();
    }

    /**
     * 3) Envío INDIVIDUAL usando "test email" a 1 correo.
     * Docs: POST /campaigns/{campaign_id}/actions/test :contentReference[oaicite:4]{index=4}
     */
    public function sendTestEmail(string $campaignId, string $email): array
    {
        $payload = [
            'test_emails' => [$email],
            'send_type' => 'html',
        ];

        $res = $this->client()->post($this->baseUrl . "/campaigns/{$campaignId}/actions/test", $payload);

        if ($res->failed()) {
            throw new RequestException($res);
        }

        // Mailchimp suele devolver 204 No Content cuando todo sale bien
        return $res->json() ?? ['ok' => true];
    }

    /**
     * Flujo completo: create -> set content (template) -> send test (individual)
     */
    public function sendIndividualUsingTemplate(string $toEmail, string $subject, int $templateId, array $sections = []): array
    {
        $campaign = $this->createCampaign($subject);
        $campaignId = $campaign['id'] ?? null;

        if (!$campaignId) {
            return ['ok' => false, 'error' => 'No se pudo obtener campaign_id al crear la campaña.'];
        }

        $this->setCampaignContentWithTemplate($campaignId, $templateId, $sections);
        $this->sendTestEmail($campaignId, $toEmail);

        return [
            'ok' => true,
            'campaign_id' => $campaignId,
            'sent_to' => $toEmail,
        ];
    }
}
