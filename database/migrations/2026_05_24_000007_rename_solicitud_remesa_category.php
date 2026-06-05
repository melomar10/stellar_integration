<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wasapi_template_categories')
            ->where('name', 'Solicitud de remesa')
            ->update(['name' => 'Solicitud Remesas', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('wasapi_template_categories')
            ->where('name', 'Solicitud Remesas')
            ->update(['name' => 'Solicitud de remesa', 'updated_at' => now()]);
    }
};
