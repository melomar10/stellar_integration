<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlfredAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'alfred_customer_id',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'main_nationality',
        'secondary_nationalities',
        'email',
        'phone',
        'address',
        'residential_address',
        'street_address',
        'street_address_line2',
        'city',
        'state_province_region',
        'country',
        'postal_code',
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
        'is_active',
        'alfred_created_at',
        'alfred_updated_at',
        'links',
        'move_type',
        'role',
        'on_ramp_country',
        'off_ramp_country',
        'on_ramp_currency',
        'off_ramp_currency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'alfred_created_at' => 'datetime',
        'alfred_updated_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_pep' => 'boolean',
        'email_verified' => 'boolean',
        'phone_number_verified' => 'boolean',
        'links' => 'array',
        'files' => 'array',
        'extras' => 'array',
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'alfred_account_id');
    }
}

