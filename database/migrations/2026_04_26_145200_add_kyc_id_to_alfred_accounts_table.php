<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alfred_accounts', function (Blueprint $table) {
            $table->string('kyc_id')->nullable()->index()->after('alfred_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('alfred_accounts', function (Blueprint $table) {
            $table->dropColumn('kyc_id');
        });
    }
};

