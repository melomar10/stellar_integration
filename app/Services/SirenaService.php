<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Tranfer;

class SirenaService
{
    private function getSupplier()
    {
       $supplier = Supplier::where('name', 'Sirena')->first();
       return $supplier;
    }

    /**
     * Obtiene la tasa de cambio y cálculo de recarga
     * 
     * @param float $total Monto en dólares
     * @return array
     */
    public function getRechargeResume($total)
    {
        try {
            $response = Http::get( $this->getSupplier()->url . '/app/getRechargeResume', [
                'total' => $total
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'ok' => false,
                'message' => 'Error al obtener la tasa de cambio',
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
     * Obtiene las sucursales por provincia con formato estructurado
     * 
     * @param string $provinceId ID de la provincia
     * @return array
     */
    public function getCompaniesByProvince($provinceId)
    {
        try {
            $response = Http::get($this->getSupplier()->url . "/app/getCompaniesByProvince/{$provinceId}");

            if ($response->successful()) {
                $data = $response->json();
                
                // Procesar las provincias para crear el array estructurado
                if (isset($data['data']) && isset($data['data']['Province']) && isset($data['data']['IdProvinces'])) {
                    $provinces = explode(',', $data['data']['Province']);
                    $idProvinces = explode(',', $data['data']['IdProvinces']);
                    
                    $provincesArray = [];
                    for ($i = 0; $i < count($provinces); $i++) {
                        if (isset($idProvinces[$i])) {
                            $provincesArray[] = [
                                'id_province' => trim($idProvinces[$i]),
                                'province' => trim($provinces[$i])
                            ];
                        }
                    }
                    
                    // Agregar el array de provincias estructurado a la respuesta
                    $data['data']['provinces'] = $provincesArray;
                }

                return $data;
            }

            return [
                'ok' => false,
                'message' => 'Error al obtener las sucursales',
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
     * Solicita un bono/pago con conversión de pesos a dólares
     * 
     * @param array $params Parámetros del pago
     * @return array
     */
    public function requestBonus($params)
    {
        try {
            // Validar parámetros requeridos
            if (!isset($params['user_id']) || !isset($params['amount'])) {
                return [
                    'ok' => false,
                    'message' => 'user_id y amount son requeridos',
                    'data' => null
                ];
            }

            // Buscar el cliente
            $client = Client::where('uuid', $params['user_id'])->first();
            if (!$client) {
                return [
                    'ok' => false,
                    'message' => 'Cliente no encontrado',
                    'data' => null
                ];
            }

            // 1. Obtener tasa de cambio con monto base de 10 dólares
            $baseResponse = $this->getRechargeResume(10);
            if (!$baseResponse['ok']) {
                return $baseResponse;
            }

            $convertionRate = $baseResponse['data']['convertion_rate'];

            // 2. Convertir monto en pesos a dólares
            $amountPesos = $params['amount'];
            $amountUsd = $amountPesos / $convertionRate;

            // 3. Obtener invoice_info con el monto convertido
            $invoiceResponse = $this->getRechargeResume($amountUsd);
            if (!$invoiceResponse['ok']) {
                return $invoiceResponse;
            }

            $invoiceInfo = $invoiceResponse['data'];

            // 4. Obtener company_id desde getCompaniesByProvince
            // Por ahora usamos un ID de provincia por defecto, puedes ajustarlo según necesites
            $companiesResponse = $this->getCompaniesByProvince('3w88aXrcoodCn8n2CR2v');
            $companyId = 'Sirena@domi.com'; // Valor por defecto, ajustar según la respuesta

            if ($companiesResponse['ok'] && isset($companiesResponse['data']['company_id'])) {
                $companyId = $companiesResponse['data']['company_id'];
            }

            // 5. Preparar datos para la petición
            $requestData = [
                'company_id' => $companyId,
                'note' => $params['note'] ?? '',
                'user_id' => $params['user_id'],
                'receiver_phone' => $client->phone,
                'receiver_name' => $client->name . ' ' . $client->last_name,
                'amount' => $amountUsd,
                'invoice_info' => $invoiceInfo,
                'receiver_reference' => $client->card_number_id ?? '',
                'email' => $client->email
            ];

            // 6. Realizar petición al API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getSupplier()->token
            ])->post($this->getSupplier()->url . '/app/requestBonus', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['ok']) {
                    // Construir transfer_url
                    $transferUrl = "https://domipagosclient.web.app/#/pay_bonus/{$responseData['data']['id']}";

                    $transfer = new Tranfer();
                    $transfer->client_id = $client->id;
                    $transfer->supplier_id = $this->getSupplier()->id;
                    $transfer->amount = $amountUsd;
                    $transfer->transfer_status = 'pending';
                    $transfer->note = 'Pago de bono Sirena';
                    $transfer->save();
                    
                    return [
                        'ok' => true,
                        'data' => [
                            'user_data' => [
                                'name' => $client->name,
                                'last_name' => $client->last_name,
                                'email' => $client->email
                            ],
                            'amount_pesos' => round($amountPesos, 2),
                            'amount_usd' => round($amountUsd, 2),
                            'service_fee_usd' => round($invoiceInfo['service_fee_usd'], 2),
                            'convertion_rate' => round($convertionRate, 2),
                            'total_usd' => round($invoiceInfo['total_usd'], 2),
                            'total_pesos' => round($invoiceInfo['total_pesos'], 2),
                            'transfer_url' => $transferUrl,
                            'payment_id' => $responseData['data']['id']
                        ]
                    ];
                }

                return $responseData;
            }

            return [
                'ok' => false,
                'message' => 'Error al procesar el pago',
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