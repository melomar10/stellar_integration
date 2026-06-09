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

    /**
     * Marca como completada la solicitud activa que corresponde a una orden Alfred.
     */
    public static function completeForOrder(
        ?string $senderCustomerId,
        ?string $receiverCustomerId,
        ?string $senderPhone = null,
        ?string $receiverPhone = null,
        float|string|null $amount = null
    ): ?self {
        $transferRequest = self::findActiveForOrder(
            $senderCustomerId,
            $receiverCustomerId,
            $senderPhone,
            $receiverPhone,
            $amount,
            true
        ) ?? self::findActiveForOrder(
            $senderCustomerId,
            $receiverCustomerId,
            $senderPhone,
            $receiverPhone,
            $amount,
            false
        );

        if ($transferRequest === null) {
            return null;
        }

        $transferRequest->update(['status' => self::STATUS_COMPLETADA]);

        return $transferRequest->fresh();
    }

    private static function findActiveForOrder(
        ?string $senderCustomerId,
        ?string $receiverCustomerId,
        ?string $senderPhone,
        ?string $receiverPhone,
        float|string|null $amount,
        bool $matchAmount
    ): ?self {
        $senderCustomerId = trim((string) ($senderCustomerId ?? ''));
        $receiverCustomerId = trim((string) ($receiverCustomerId ?? ''));
        $senderPhone = self::normalizePhone((string) ($senderPhone ?? ''));
        $receiverPhone = self::normalizePhone((string) ($receiverPhone ?? ''));

        if (
            ($senderCustomerId === '' || $receiverCustomerId === '')
            && ($senderPhone === '' || $receiverPhone === '')
        ) {
            return null;
        }

        $query = self::query()
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderByDesc('created_at');

        $query->where(function ($outer) use ($senderCustomerId, $receiverCustomerId, $senderPhone, $receiverPhone) {
            if ($senderCustomerId !== '' && $receiverCustomerId !== '') {
                $outer->where(function ($inner) use ($senderCustomerId, $receiverCustomerId) {
                    $inner->where('sender_customer_id', $senderCustomerId)
                        ->where('receiver_customer_id', $receiverCustomerId);
                });
            }

            if ($senderPhone !== '' && $receiverPhone !== '') {
                $phoneMatch = function ($inner) use ($senderPhone, $receiverPhone) {
                    $inner->where('sender_phone', $senderPhone)
                        ->where('receiver_phone', $receiverPhone);
                };

                if ($senderCustomerId !== '' && $receiverCustomerId !== '') {
                    $outer->orWhere($phoneMatch);
                } else {
                    $outer->where($phoneMatch);
                }
            }
        });

        if ($matchAmount && $amount !== null && $amount !== '') {
            $query->where('amount', number_format((float) $amount, 2, '.', ''));
        }

        return $query->first();
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
