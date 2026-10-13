<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data demo deterministik untuk project PetMarket.
     */
    public function run(): void
    {
        // Daftar akun demo sesuai modul PetMarket
        $demo = [
            ['nama' => 'Admin PetMarket', 'email' => 'admin@petmarket.test', 'role' => 'SuperAdmin'],
            ['nama' => 'Operator Tenant', 'email' => 'tenant@petmarket.test', 'role' => 'Tenant'],
            ['nama' => 'Pelanggan Demo', 'email' => 'pelanggan@petmarket.test', 'role' => 'Pelanggan'],
        ];

        foreach ($demo as $row) {
            Pengguna::firstOrNew(['email' => $row['email']])
                ->forceFill([
                    'id' => (string) Str::uuid(), // Wajib ada karena ID kita string/UUID
                    'nama' => $row['nama'],          // Sesuai nama kolom di migration
                    'role' => $row['role'],          // Sesuai pilihan enum di migration
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ])
                ->save();
        }
    }
}
