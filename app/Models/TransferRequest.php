<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransferRequest extends Model
{
    public const STATUS_SOLICITADA = 'solicitada';

    public const STATUS_RECHAZADA = 'rechazada';

    public const STATUS_APROBADO = 'aprobado';

    public const STATUS_COMPLETADA = 'completada';

    public const STATUS_CANCELADA = 'cancelada';

    public const STATUSES = [
        self::STATUS_SOLICITADA,
        self::STATUS_RECHAZADA,
        self::STATUS_APROBADO,
        self::STATUS_COMPLETADA,
        self::STATUS_CANCELADA,
    ];

    /** Estados considerados activos (pendientes de acción del sender). */
    public const ACTIVE_STATUSES = [
        self::STATUS_SOLICITADA,
        self::STATUS_APROBADO,
    ];

    protected $fillable = [
        'uuid',
        'sender_phone',
        'receiver_phone',
        'amount',
        'currency',
        'status',
        'sender_customer_id',
        'receiver_customer_id',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (TransferRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
            if (empty($request->status)) {
                $request->status = self::STATUS_SOLICITADA;
            }
        });
    }

    public static function normalizePhone(string $input): string
    {
        $raw = preg_replace('/[^0-9]/', '', $input);
        if ($raw === '') {
            return '';
        }

        return substr($raw, 0, 1) !== '1' ? '1' . $raw : $raw;
    }

    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 2, '.', '');
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_SOLICITADA,
            self::STATUS_APROBADO,
        ], true);
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        if (array_key_exists('amount', $array) && $array['amount'] !== null && $array['amount'] !== '') {
            $array['amount'] = number_format((float) $array['amount'], 2, '.', '');
        }

        return $array;
    }
}
