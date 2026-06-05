<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('sender_phone', 20);
            $table->string('receiver_phone', 20);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 32)->default('solicitada');
            $table->uuid('sender_customer_id')->nullable();
            $table->uuid('receiver_customer_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('sender_phone');
            $table->index('receiver_phone');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
