<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('alfred_account_id')->nullable()->after('country');
            $table->foreign('alfred_account_id')
                ->references('id')
                ->on('alfred_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['alfred_account_id']);
            $table->dropColumn('alfred_account_id');
        });
    }
};

