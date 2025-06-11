<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('source_currency');
            $table->string('destination_payment_rail');
            $table->string('destination_currency');
            $table->string('destination_address');
            $table->string('developer_fee_percent')->nullable();
            $table->string('bridge_virtual_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_accounts');
    }
}; 