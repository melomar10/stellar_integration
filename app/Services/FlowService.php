<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FlowService
{
    /**
     * Parsea el string JSON del flow y retorna un array manejable
     * 
     * @param string $flowString String JSON del flow
     * @return array|null Array con los datos parseados o null si hay error
     */
    public function parseFlow(string $flowString): ?array
    {
        try {
            $decoded = json_decode($flowString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Error al parsear flow JSON: ' . json_last_error_msg(), [
                    'flow_string' => $flowString
                ]);
                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('Excepción al parsear flow: ' . $e->getMessage(), [
                'flow_string' => $flowString
            ]);
            return null;
        }
    }

    /**
     * Extrae los datos del cliente desde el flow parseado
     * 
     * @param array $flowData Datos del flow parseados
     * @return array Datos del cliente extraídos
     */
    public function extractClientData(array $flowData): array
    {
        // Extraer nombre completo (puede venir en diferentes campos según el flow)
        $fullName = '';
        foreach ($flowData as $key => $value) {
            if (stripos($key, 'nombre') !== false || stripos($key, 'name') !== false) {
                $fullName = $value;
                break;
            }
        }

        // Separar nombre y apellido
        $nameParts = explode(' ', trim($fullName));
        $name = $nameParts[0] ?? '';
        $lastName = implode(' ', array_slice($nameParts, 1)) ?? '';

        // Extraer email
        $email = '';
        foreach ($flowData as $key => $value) {
            if (stripos($key, 'email') !== false) {
                $email = $value;
                break;
            }
        }

        // Extraer cédula
        $cardNumberId = '';
        foreach ($flowData as $key => $value) {
            if (stripos($key, 'cedula') !== false || stripos($key, 'ced') !== false) {
                $cardNumberId = $value;
                break;
            }
        }

        // Extraer flow_token si existe
        $flowToken = $flowData['flow_token'] ?? null;

        return [
            'name' => $name,
            'last_name' => $lastName,
            'email' => $email ?: null,
            'card_number_id' => $cardNumberId ?: null,
            'flow_token' => $flowToken,
        ];
    }

    /**
     * Procesa el flow completo y retorna los datos del cliente listos para crear
     * 
     * @param string $flowString String JSON del flow
     * @return array|null Datos del cliente o null si hay error
     */
    public function processFlow(string $flowString): ?array
    {
        $flowData = $this->parseFlow($flowString);
        
        if ($flowData === null) {
            return null;
        }

        return $this->extractClientData($flowData);
    }
}

