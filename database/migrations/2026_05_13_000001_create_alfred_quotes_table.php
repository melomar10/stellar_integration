<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('alfred_customer_id', 64)->index();
            $table->string('quote_id', 64)->unique();
            $table->decimal('from_amount', 24, 8)->nullable();
            $table->string('rate', 64)->nullable();
            $table->string('to_amount', 64)->nullable();
            $table->unsignedBigInteger('expiration')->nullable()->comment('Epoch ms u valor devuelto por Alfred');
            $table->string('on_ramp_external_quote_id', 64)->nullable();
            $table->string('off_ramp_external_quote_id', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_quotes');
    }
};
