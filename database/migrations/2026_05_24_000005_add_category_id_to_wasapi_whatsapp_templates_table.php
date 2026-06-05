<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wasapi_whatsapp_templates', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('status')
                ->constrained('wasapi_template_categories')
                ->nullOnDelete();

            $table->unique('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('wasapi_whatsapp_templates', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
