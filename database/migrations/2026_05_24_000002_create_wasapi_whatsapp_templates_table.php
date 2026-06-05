<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wasapi_whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wasapi_id')->unique();
            $table->uuid('uuid')->nullable();
            $table->string('template_id');
            $table->string('status', 32);
            $table->timestamps();

            $table->index('template_id');
            $table->index('status');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wasapi_whatsapp_templates');
    }
};
