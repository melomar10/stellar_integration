<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alfred_orders', function (Blueprint $table) {
            $table->json('payment_instructions')->nullable()->after('links');
        });
    }

    public function down(): void
    {
        Schema::table('alfred_orders', function (Blueprint $table) {
            $table->dropColumn('payment_instructions');
        });
    }
};
