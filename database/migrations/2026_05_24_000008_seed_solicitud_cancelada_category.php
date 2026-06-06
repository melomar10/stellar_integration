<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wasapi_template_categories')->insertOrIgnore([
            'name'       => 'Solicitud Cancelada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('wasapi_template_categories')->where('name', 'Solicitud Cancelada')->delete();
    }
};
