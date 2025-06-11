<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;

class QuoteMetadataDto 
{
    public function __construct(
        public string $developerId,
        public string $markupFeeRate
    ) {}
}
