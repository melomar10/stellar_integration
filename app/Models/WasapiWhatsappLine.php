<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasapiWhatsappLine extends Model
{
    protected $fillable = [
        'wasapi_id',
        'uuid',
        'user_id',
        'app_id',
        'display_name',
        'phone_number',
        'phone_digits',
        'phone_id',
        'quality_score',
        'can_send_message',
        'app_name',
        'waba_id',
        'is_default',
        'extra',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'extra'      => 'array',
    ];

    public static function defaultLine(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }

    public static function setDefault(int $localId): void
    {
        static::query()->update(['is_default' => false]);
        static::query()->whereKey($localId)->update(['is_default' => true]);
    }
}
