<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class WasapiSetting extends Model
{
    protected $fillable = [
        'api_token',
        'base_uri',
    ];

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'base_uri' => 'https://api-ws.wasapi.io/api/v1',
        ]);
    }

    public function setApiTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['api_token'] = null;

            return;
        }

        $this->attributes['api_token'] = Crypt::encryptString($value);
    }

    public function getApiTokenAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function hasToken(): bool
    {
        return trim((string) $this->api_token) !== '';
    }

    public function maskedToken(): ?string
    {
        $token = $this->api_token;
        if ($token === null || $token === '') {
            return null;
        }

        $len = strlen($token);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return substr($token, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($token, -4);
    }
}
