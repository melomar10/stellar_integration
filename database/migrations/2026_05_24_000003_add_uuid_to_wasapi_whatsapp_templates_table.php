<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wasapi_whatsapp_templates', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('wasapi_id');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('wasapi_whatsapp_templates', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
