<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasapiWhatsappTemplate extends Model
{
    protected $fillable = [
        'wasapi_id',
        'uuid',
        'template_id',
        'status',
        'category_id',
    ];

    protected $casts = [
        'wasapi_id'   => 'integer',
        'category_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasapiTemplateCategory::class, 'category_id');
    }

    public static function findByCategoryName(string $categoryName): ?self
    {
        return static::query()
            ->whereHas('category', fn ($q) => $q->where('name', $categoryName))
            ->whereNotNull('uuid')
            ->where('uuid', '!=', '')
            ->first();
    }
}
