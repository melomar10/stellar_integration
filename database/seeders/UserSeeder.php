<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario administrador de prueba
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@domipago.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Usuario agente de prueba
        User::create([
            'name' => 'Agente Prueba',
            'email' => 'agente@domipago.com',
            'password' => Hash::make('password123'),
            'role' => 'agent',
        ]);

        // Usuario API de prueba
        User::create([
            'name' => 'Usuario API',
            'email' => 'api@domipago.com',
            'password' => Hash::make('password123'),
            'role' => 'api',
        ]);
    }
}

