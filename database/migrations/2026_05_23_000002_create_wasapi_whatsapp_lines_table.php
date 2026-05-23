<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wasapi_whatsapp_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wasapi_id')->unique();
            $table->string('uuid', 64)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('display_name', 150);
            $table->string('phone_number', 32);
            $table->string('phone_digits', 20)->nullable();
            $table->string('phone_id', 64)->nullable();
            $table->string('quality_score', 32)->nullable();
            $table->string('can_send_message', 32)->nullable();
            $table->string('app_name', 150)->nullable();
            $table->string('waba_id', 64)->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wasapi_whatsapp_lines');
    }
};
