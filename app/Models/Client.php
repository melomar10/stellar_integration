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
        'alfred_account_id',
    ];

    protected $appends = [
        'is_registered_in_alfred',
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
    public function waitingList()
    {
        return $this->hasMany(WaitingList::class);
    }

    public function alfredAccount()
    {
        return $this->belongsTo(AlfredAccount::class, 'alfred_account_id');
    }

    public function getIsRegisteredInAlfredAttribute(): bool
    {
        return !is_null($this->alfred_account_id);
    }
}
