<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;

class FiatAccountResponseDto 
{
   public function __construct(
        public string $fiatAccountId,
        public string $type,
        public string $accountNumber,
        public string $accountType,
        public string $bankName,
        public string $createdAt
    ) {}
}
