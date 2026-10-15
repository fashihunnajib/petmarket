<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data demo deterministik. Password demo bersifat lokal (bukan secret produksi).
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Akun demo per konteks (Modul 2). Role diformalkan pada Modul 4–5.
        $demo = [
            ['name' => 'Admin PetMarket', 'email' => 'admin@petmarket.test', 'role' => 'admin', 'address' => null, 'phone_e164' => '+6281234567890', 'latitude' => null, 'longitude' => null],
            ['name' => 'Operator Tenant', 'email' => 'tenant@petmarket.test', 'role' => 'tenant', 'address' => 'Jl. Raya Tenant No. 1', 'phone_e164' => '+6281234567891', 'latitude' => -6.900000, 'longitude' => 107.600000],
        ];

        foreach ($demo as $row) {
            // forceFill: role/status sengaja tidak mass-assignable; forceFill melewati
            // proteksi fillable dan menghindari asignmen properti bertipe langsung.
            User::firstOrNew(['email' => $row['email']])
                ->forceFill([
                    'name' => $row['name'],
                    'role' => $row['role'],
                    'address'=> $row['address'],
                    'phone_e164' => $row['phone_e164'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'status' => 'active',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ])
                ->save();
        }

        $this->call(DemoShopSeeder::class);
    }
}
