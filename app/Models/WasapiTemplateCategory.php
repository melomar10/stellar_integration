<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WasapiTemplateCategory extends Model
{
    protected $fillable = [
        'name',
    ];

    public function template(): HasOne
    {
        return $this->hasOne(WasapiWhatsappTemplate::class, 'category_id');
    }
}
