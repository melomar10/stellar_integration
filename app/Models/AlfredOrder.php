<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlfredOrder extends Model
{
    public const STATUS_PROCESSED = 'processed';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'alfred_quote_id',
        'alfred_order_id',
        'quote_id',
        'initiating_customer_id',
        'sender_customer_id',
        'receiver_customer_id',
        'status',
        'api_status',
        'sub_status',
        'total_amount_value',
        'total_amount_currency',
        'total_amount_precision',
        'external_order_id',
        'metadata',
        'use_escrow',
        'error_message',
        'error_code',
        'alfred_created_at',
        'alfred_updated_at',
        'alfred_completed_at',
        'links',
        'payment_instructions',
    ];

    protected $casts = [
        'metadata' => 'array',
        'links' => 'array',
        'payment_instructions' => 'array',
        'use_escrow' => 'boolean',
        'alfred_created_at' => 'datetime',
        'alfred_updated_at' => 'datetime',
        'alfred_completed_at' => 'datetime',
    ];

    public function alfredQuote(): BelongsTo
    {
        return $this->belongsTo(AlfredQuote::class, 'alfred_quote_id');
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        if (array_key_exists('total_amount_value', $array) && $array['total_amount_value'] !== null && $array['total_amount_value'] !== '') {
            $array['total_amount_value'] = number_format((float) $array['total_amount_value'], 2, '.', '');
        }

        return $array;
    }
}
