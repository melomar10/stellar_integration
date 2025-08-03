<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tranfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'supplier_id',
        'amount',
        'status',
        'note',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
