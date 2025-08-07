<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShortLinkService
{
    private $apiKey;
    private $domain;
    private $baseUrl = 'https://api.short.io/links';

    public function __construct()
    {
        $this->apiKey =  'sk_sthxGQKqdsINUmy2';
        $this->domain =  'domibono.short.gy';
    }

    /**
     * Crea un enlace corto usando la API de Short.io
     * 
     * @param string $originalUrl La URL original que se quiere acortar
     * @param int|null $ttl Tiempo de vida del enlace en segundos (opcional)
     * @param string|null $path Slug personalizado (opcional)
     * @return array
     */
    public function createShortLink($originalUrl, $ttl = null, $path = null)
    {
        try {
            $payload = [
                'allowDuplicates' => false,
                'originalURL' => $originalUrl,
                'domain' => $this->domain
            ];

            // Agregar TTL si se proporciona - debe ser una fecha futura
            if ($ttl) {
                // Convertir segundos a fecha futura
                $expirationDate = date('Y-m-d\TH:i:s.v\Z', time() + $ttl);
                $payload['ttl'] = $expirationDate;
            }

            // Agregar path personalizado si se proporciona
            if ($path) {
                $payload['path'] = $path;
            }

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Short link created successfully', [
                    'original_url' => $originalUrl,
                    'short_url' => $data['shortURL'] ?? null,
                    'id' => $data['idString'] ?? null
                ]);

                return [
                    'ok' => true,
                    'data' => [
                        'original_url' => $originalUrl,
                        'short_url' => $data['shortURL'] ?? null,
                        'secure_short_url' => $data['secureShortURL'] ?? null,
                        'id' => $data['idString'] ?? null,
                        'path' => $data['path'] ?? null,
                        'created_at' => $data['createdAt'] ?? null
                    ]
                ];
            }

            Log::error('Error creating short link', [
                'original_url' => $originalUrl,
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return [
                'ok' => false,
                'message' => 'Error al crear el enlace corto: ' . $response->body(),
                'data' => null
            ];

        } catch (\Exception $e) {
            Log::error('Exception creating short link', [
                'original_url' => $originalUrl,
                'error' => $e->getMessage()
            ]);

            return [
                'ok' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Actualiza un enlace corto existente
     * 
     * @param string $linkId ID del enlace a actualizar
     * @param array $updateData Datos a actualizar
     * @return array
     */
    public function updateShortLink($linkId, $updateData)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ])->put($this->baseUrl . '/' . $linkId, $updateData);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'ok' => false,
                'message' => 'Error al actualizar el enlace corto: ' . $response->body(),
                'data' => null
            ];

        } catch (\Exception $e) {
            return [
                'ok' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Elimina un enlace corto
     * 
     * @param string $linkId ID del enlace a eliminar
     * @return array
     */
    public function deleteShortLink($linkId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json'
            ])->delete($this->baseUrl . '/' . $linkId);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Enlace eliminado correctamente'
                ];
            }

            return [
                'ok' => false,
                'message' => 'Error al eliminar el enlace corto: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'ok' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene información de un enlace corto
     * 
     * @param string $linkId ID del enlace
     * @return array
     */
    public function getShortLinkInfo($linkId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/' . $linkId);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'ok' => false,
                'message' => 'Error al obtener información del enlace: ' . $response->body(),
                'data' => null
            ];

        } catch (\Exception $e) {
            return [
                'ok' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
} 