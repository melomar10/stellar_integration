<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;

class QuoteResponseDto 
{
      /**
     * @param QuoteFeeDto[] $fees
     */
    public function __construct(
        public string $quoteId,
        public string $fromCurrency,
        public string $toCurrency,
        public string $fromAmount,
        public string $toAmount,
        public string $expiration,
        public array $fees, // array de QuoteFeeDto
        public string $rate,
        public QuoteMetadataDto $metadata
    ) {}
}
