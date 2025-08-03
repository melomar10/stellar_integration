<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'token',
        'url',
        'status',
    ];
    public function transfers()
    {
        return $this->hasMany(Tranfer::class);
    }
}
