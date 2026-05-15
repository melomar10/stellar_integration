<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alfred_quotes', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->after('quote_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('alfred_quotes', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
