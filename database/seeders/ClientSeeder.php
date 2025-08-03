<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use Ramsey\Uuid\Uuid;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::create([
            'name' => 'Dariel',
            'last_name' => 'Abreu',
            'email' => 'luisdanielcurso@gmail.com',
            'phone' => '+1 829-873-6708',
            'uuid' => 'R3kAJiMQZagWmQPIAkdsaG5stME2',
            'status' => 'active',
            'card_number_id' => '40227520364'
        ]);

        $this->command->info("Cliente de ejemplo creado con UUID: {$client->uuid}");
    }
} 