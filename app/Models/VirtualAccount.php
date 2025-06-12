<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAccount extends Model
{
    protected $fillable = [
        'customer_id',
        'source_currency',
        'destination_payment_rail',
        'destination_currency',
        'destination_address',
        'developer_fee_percent',
        'bridge_virtual_account_id'
    ];

    /**
     * Obtiene el cliente al que pertenece esta cuenta virtual.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
} 