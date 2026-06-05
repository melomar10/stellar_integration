<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $categories = [
            'Enviar Remesa',
            'Recibir Remesa',
            'Solicitud Remesas',
            'Remesa enviada',
            'Remesa recibida',
        ];

        foreach ($categories as $name) {
            DB::table('wasapi_template_categories')->insertOrIgnore([
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('wasapi_template_categories')->whereIn('name', [
            'Enviar Remesa',
            'Recibir Remesa',
            'Solicitud Remesas',
            'Solicitud de remesa',
            'Remesa enviada',
            'Remesa recibida',
        ])->delete();
    }
};
