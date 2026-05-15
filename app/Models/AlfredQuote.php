<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlfredQuote extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'alfred_customer_id',
        'quote_id',
        'status',
        'from_amount',
        'rate',
        'to_amount',
        'expiration',
        'on_ramp_external_quote_id',
        'off_ramp_external_quote_id',
    ];

    protected $casts = [
        'expiration' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(AlfredOrder::class, 'alfred_quote_id');
    }

    /**
     * Montos con 2 decimales en respuestas JSON del API.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        foreach (['from_amount', 'rate', 'to_amount'] as $key) {
            if (! array_key_exists($key, $array) || $array[$key] === null || $array[$key] === '') {
                continue;
            }
            $array[$key] = number_format((float) $array[$key], 2, '.', '');
        }

        return $array;
    }
}
