<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;

class QuoteFeeDto 
{
     public function __construct(
        public string $type,
        public string $amount,
        public string $currency
    ) {}
}
