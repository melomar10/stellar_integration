<?php

namespace App\Models;

use App\Models\Flows\StepByFlow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone',
        'uuid',
        'card_number_id',
        'has_account',
        'country',
        'status',
    ];

    public function generateUuid()
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->save();
        return $this;
    }
    public function transfers()
    {
        return $this->hasMany(Tranfer::class);
    }
    public function stepByFlows()
    {
        return $this->hasMany(StepByFlow::class);
    }
}
