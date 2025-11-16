<?php

namespace App\Models\Flows;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StepByFlow extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'name',
        'description',
        'type',
    ];
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
