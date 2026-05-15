<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alfred_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alfred_quote_id')->nullable()->constrained('alfred_quotes')->nullOnDelete();
            $table->string('alfred_order_id', 64)->unique();
            $table->string('quote_id', 64)->index();
            $table->string('initiating_customer_id', 64);
            $table->string('sender_customer_id', 64)->index();
            $table->string('receiver_customer_id', 64)->index();
            $table->string('status', 32)->default('processed')->index();
            $table->string('api_status', 32)->nullable();
            $table->string('sub_status', 64)->nullable();
            $table->string('total_amount_value', 64)->nullable();
            $table->string('total_amount_currency', 8)->nullable();
            $table->unsignedTinyInteger('total_amount_precision')->nullable();
            $table->string('external_order_id', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('use_escrow')->default(true);
            $table->text('error_message')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('alfred_created_at')->nullable();
            $table->timestamp('alfred_updated_at')->nullable();
            $table->timestamp('alfred_completed_at')->nullable();
            $table->json('links')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alfred_orders');
    }
};
