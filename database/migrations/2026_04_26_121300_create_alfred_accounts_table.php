<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_accounts', function (Blueprint $table) {
            $table->id();

            // ID del customer en Alfred (UUID string)
            $table->string('alfred_customer_id')->unique();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();

            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();

            $table->boolean('is_active')->default(true)->index();

            // Timestamps provenientes de Alfred
            $table->timestamp('alfred_created_at')->nullable();
            $table->timestamp('alfred_updated_at')->nullable();

            // _links del API
            $table->json('links')->nullable();

            // Timestamps locales
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_accounts');
    }
};

