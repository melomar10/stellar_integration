<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        Supplier::create([ 
            'name' => 'Sirena',
            'token' => 'eyJhbGciOiJSUzI1NiIsImtpZCI6Ijk1MWRkZTkzMmViYWNkODhhZmIwMDM3YmZlZDhmNjJiMDdmMDg2NmIiLCJ0eXAiOiJKV1QifQ.eyJwcm92aWRlcl9pZCI6ImFub255bW91cyIsImlzcyI6Imh0dHBzOi8vc2VjdXJldG9rZW4uZ29vZ2xlLmNvbS9kYmRvbWljYW5hc3RhcyIsImF1ZCI6ImRiZG9taWNhbmFzdGFzIiwiYXV0aF90aW1lIjoxNzUyODcyMzI3LCJ1c2VyX2lkIjoiYTc3a0hLMjJ5VVhRa3plMEFscDNLWWdVbjUxMiIsInN1YiI6ImE3N2tISzIyeVVYUWt6ZTBBbHAzS1lnVW41MTIiLCJpYXQiOjE3NTQxNzI1NTYsImV4cCI6MTc1NDE3NjE1NiwiZmlyZWJhc2UiOnsiaWRlbnRpdGllcyI6e30sInNpZ25faW5fcHJvdmlkZXIiOiJhbm9ueW1vdXMifX0.YIUDRqPoxtLhxjTHsQ55Zqm08Q-4DF6E4Bzz3Wx34S5VcbDAd-E5xZF9vAZPZ-2ln-vE-tUSpYqPy9mFZ2CZSD8tyUSc76LapzfJxV6irsVIbW2uRv5oo4P94544ZpkmREivPQqLoq3A_wnila9cvsx2TYNf83PIio_zmew6oXdDbYZ8MKh78VRv6JspShPKKsyWlkAaHWKRpou_5L8pPjLS6yhN4yCYp-vhghYr_40Yn3auPEgfEbqEDkSk5BhdRDHaMLYtoTJkuJxxHtoj_OZfiv6fvbwTX-ZzPoQDNTtXNtypJvEcw7tyeJ1lfHCZcJUz0nzrFfhUjBN1URVNUQ',
            'url' => 'https://us-central1-dbdomicanastas.cloudfunctions.net',
        ]);
    }
}