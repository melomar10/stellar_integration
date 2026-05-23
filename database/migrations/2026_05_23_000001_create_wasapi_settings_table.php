<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wasapi_settings', function (Blueprint $table) {
            $table->id();
            $table->text('api_token')->nullable();
            $table->string('base_uri', 255)->default('https://api-ws.wasapi.io/api/v1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wasapi_settings');
    }
};
