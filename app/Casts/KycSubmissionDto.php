<?php

namespace App\Casts;

use Illuminate\Database\Eloquent\Model;

class KycSubmissionDto
{
    public function __construct(
        public string $submissionId,
        public string $createdAt,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $dateOfBirth = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?string $zipCode = null,
        public ?string $address = null,
        public ?string $state = null,
        public array $nationalities = [],
        public ?string $phoneNumber = null,
        public ?string $occupation = null,
        public ?string $email = null,
    ) {}
}
