<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alfred_accounts', function (Blueprint $table) {
            $table->string('move_type')->nullable()->index()->after('links');
            $table->string('role')->nullable()->index()->after('move_type');

            $table->string('on_ramp_country', 10)->nullable()->after('role');
            $table->string('off_ramp_country', 10)->nullable()->after('on_ramp_country');
            $table->string('on_ramp_currency', 10)->nullable()->after('off_ramp_country');
            $table->string('off_ramp_currency', 10)->nullable()->after('on_ramp_currency');

            $table->string('middle_name')->nullable()->after('last_name');
            $table->string('gender')->nullable()->after('middle_name');

            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');

            $table->string('main_nationality', 10)->nullable()->after('place_of_birth');
            $table->string('secondary_nationalities')->nullable()->after('main_nationality');

            $table->string('residential_address')->nullable()->after('address');
            $table->string('street_address')->nullable()->after('residential_address');
            $table->string('street_address_line2')->nullable()->after('street_address');
            $table->string('state_province_region')->nullable()->after('city');

            $table->string('preferred_language', 10)->nullable()->after('state_province_region');
            $table->boolean('is_pep')->nullable()->after('preferred_language');

            $table->string('dni')->nullable()->after('is_pep');
            $table->string('national_id')->nullable()->after('dni');
            $table->string('cpf')->nullable()->after('national_id');
            $table->string('license_id')->nullable()->after('cpf');

            $table->boolean('email_verified')->nullable()->after('license_id');
            $table->boolean('phone_number_verified')->nullable()->after('email_verified');

            $table->string('file_types')->nullable()->after('phone_number_verified');
            $table->json('files')->nullable()->after('file_types');
            $table->json('extras')->nullable()->after('files');
        });
    }

    public function down(): void
    {
        Schema::table('alfred_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'move_type',
                'role',
                'on_ramp_country',
                'off_ramp_country',
                'on_ramp_currency',
                'off_ramp_currency',
                'middle_name',
                'gender',
                'date_of_birth',
                'place_of_birth',
                'main_nationality',
                'secondary_nationalities',
                'residential_address',
                'street_address',
                'street_address_line2',
                'state_province_region',
                'preferred_language',
                'is_pep',
                'dni',
                'national_id',
                'cpf',
                'license_id',
                'email_verified',
                'phone_number_verified',
                'file_types',
                'files',
                'extras',
            ]);
        });
    }
};

