<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Flows\StepByFlow;

class StepByFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        
        $stepNames = [
            'Registro Inicial',
            'Verificación de Identidad',
            'Validación de Documentos',
            'Aprobación de Cuenta',
            'Activación de Servicios',
            'Configuración de Perfil',
            'Primera Transacción',
            'Verificación de Teléfono',
            'Completado'
        ];

        $stepTypes = [
            'registration',
            'verification',
            'validation',
            'approval',
            'activation',
            'configuration',
            'transaction',
            'verification',
            'completed'
        ];

        $stepDescriptions = [
            'Cliente se registró en el sistema',
            'Proceso de verificación de identidad iniciado',
            'Documentos enviados para validación',
            'Cuenta aprobada y lista para usar',
            'Servicios activados exitosamente',
            'Perfil configurado correctamente',
            'Primera transacción realizada',
            'Número de teléfono verificado',
            'Proceso completado exitosamente'
        ];

        foreach ($clients as $client) {
            // Generar entre 3 y 5 steps por cliente
            $numberOfSteps = rand(6, 10);
            $selectedSteps = array_rand($stepNames, $numberOfSteps);
            
            // Asegurar que selectedSteps sea un array
            if (!is_array($selectedSteps)) {
                $selectedSteps = [$selectedSteps];
            }

            // Ordenar los índices para mantener un orden cronológico
            sort($selectedSteps);

            foreach ($selectedSteps as $index => $stepIndex) {
                // Crear steps con fechas progresivas (más antiguos primero)
                $createdAt = now()->subDays($numberOfSteps - $index)->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                
                StepByFlow::create([
                    'client_id' => $client->id,
                    'name' => $stepNames[$stepIndex],
                    'description' => $stepDescriptions[$stepIndex],
                    'type' => $stepTypes[$stepIndex],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $this->command->info('Steps by flow creados para todos los clientes');
    }
}

