<?php

namespace App\Models;

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
        'status',
    ];

    public function generateUuid()
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->save();
        return $this;
    }
}
