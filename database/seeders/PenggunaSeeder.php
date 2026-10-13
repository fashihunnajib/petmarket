<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengguna;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        // Membuat data SuperAdmin bawaan
        Pengguna::create([
            'id' => (string) Str::uuid(),
            'nama' => 'Super Admin PetMarket',
            'email' => 'admin@petmarket.com',
            'password' => Hash::make('password123'),
            'role' => 'SuperAdmin',
        ]);
    }
}