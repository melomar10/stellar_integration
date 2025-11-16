<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomiPagoService
{
    private $baseUrl = 'https://us-central1-dbdomicanastas.cloudfunctions.net/app';

    /**
     * Verifica si un receptor tiene cuenta en DomiPago
     * 
     * @param string $phone Número de teléfono normalizado
     * @return array
     */
    public function getReceiverHasAccount($phone): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/getReceiverHasAccount/{$phone}");

            if ($response->successful()) {
                $data = $response->json();
                
                // Verificar que la respuesta tenga la estructura esperada
                if (isset($data['ok']) && $data['ok'] === true && isset($data['hasAccount'])) {
                    return $data;
                }

                return [
                    'ok' => false,
                    'hasAccount' => false,
                    'message' => 'Respuesta inválida del servicio',
                    'data' => null
                ];
            }

            // Si la respuesta no es exitosa, asumimos que no tiene cuenta
            return [
                'ok' => false,
                'hasAccount' => false,
                'message' => 'Error al consultar el servicio',
                'data' => null
            ];

        } catch (\Exception $e) {
            return [
                'ok' => false,
                'hasAccount' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Extrae los datos del cliente desde la respuesta de la API
     * 
     * @param array $apiResponse Respuesta de la API
     * @param string $phone Teléfono normalizado
     * @return array Datos del cliente para crear en la BD
     */
    public function extractClientData(array $apiResponse, string $phone): array
    {
        $data = $apiResponse['data'] ?? [];
        $userDetails = $data['userDetails'] ?? [];
        $identity = $data['identity'] ?? [];
        $legalName = $identity['legalName'] ?? [];

        // Extraer nombre y apellido
        $name = '';
        $lastName = '';

        // Intentar obtener de userDetails.name primero
        if (isset($userDetails['name']) && !empty($userDetails['name'])) {
            $fullName = explode(' ', trim($userDetails['name']));
            $name = $fullName[0] ?? '';
            $lastName = implode(' ', array_slice($fullName, 1)) ?? '';
        }
        
        // Si no hay nombre completo, usar legalName
        if (empty($name) && isset($legalName['first'])) {
            $name = $legalName['first'] ?? '';
        }
        
        if (empty($lastName) && isset($legalName['last'])) {
            $lastName = $legalName['last'] ?? '';
        }

        // Si aún no hay nombre, usar el nombre completo de legalName
        if (empty($name) && empty($lastName) && isset($legalName['middle'])) {
            $name = $legalName['middle'] ?? '';
        }

        // Mapear status: 'active' = true, otros = false
        $status = isset($data['status']) && $data['status'] === 'active';

        return [
            'name' => $name ?: 'Sin nombre',
            'last_name' => $lastName ?: '',
            'email' => null, // No viene en la respuesta
            'phone' => $phone,
            'card_number_id' => null, // No viene en la respuesta
            'status' => $status,
            'has_account' => $apiResponse['hasAccount'],
            'country' => $apiResponse['data']['country']

        ];
    }
}

