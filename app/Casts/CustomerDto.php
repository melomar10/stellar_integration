<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;

class CustomerDto 
{
     public function __construct(
        public string $customerId,
        public string $createdAt,
    ) {}
}
