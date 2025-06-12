<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street_line_1',
        'city',
        'country',
        'birth_date',
        'signed_agreement_id',
        'bridge_customer_id'
    ];

    protected $casts = [
        'birth_date' => 'date'
    ];

    /**
     * Obtiene las cuentas virtuales asociadas a este cliente.
     */
    public function virtualAccounts(): HasMany
    {
        return $this->hasMany(VirtualAccount::class);
    }
} 