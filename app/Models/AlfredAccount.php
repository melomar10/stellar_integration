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
        'last_name',
        'full_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'is_active',
        'alfred_created_at',
        'alfred_updated_at',
        'links',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'alfred_created_at' => 'datetime',
        'alfred_updated_at' => 'datetime',
        'links' => 'array',
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'alfred_account_id');
    }
}

