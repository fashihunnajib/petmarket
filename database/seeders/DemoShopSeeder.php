<?php

namespace Database\Seeders;

use App\Models\CommissionScheme;
use App\Models\Product;
use App\Models\ProductAnimal;
use App\Models\ProductCategory;
use App\Models\ProductGood;
use App\Models\Tenant;
use App\Models\TenantBalance;
use App\Models\User;
use App\Models\UserTenantRole;
use Illuminate\Database\Seeder;

/**
 * Data demo deterministik & idempoten: 2 tenant (Pet Shop demo), saldo, komisi,
 * kategori produk (hewan & barang), produk hewan (ProductAnimal), produk barang (ProductGood),
 * serta peran operator tenant.
 *
 * Idempoten via firstOrNew + forceFill (kolom guarded seperti tenant_id di-set eksplisit).
 */
class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        $tenantsBlueprint = [
            'DEMO' => [
                'slug' => 'demo',
                'name' => 'PetMarket Demo Shop',
                'tax_rate' => 0.1000,
                'service_fee_rate' => 0.0200,
                'latitude' => -6.91750000,
                'longitude' => 107.61910000,
                'max_delivery_radius_km' => 15.00,
                'balance' => 5000000,
                'commission_rate' => 0.1500,
                'categories' => [
                    'Hewan Peliharaan' => [
                        'sort_order' => 1,
                        'animals' => [
                            [
                                'name' => 'Kucing Persia Medium',
                                'base_price' => 1500000,
                                'stock_qty' => 3,
                                'description' => 'Kucing Persia Medium usia 3 bulan, lincah, bulu lebat terawat.',
                                'detail' => [
                                    'breed' => 'Persia Medium',
                                    'age_months' => 3,
                                    'gender' => 'female',
                                    'vaccination_status' => 'fully vaccinated',
                                    'weight_kg' => 2.8,
                                    'health_history' => 'Vaksin tricat lengkap, bebas kutu & jamur.',
                                ],
                            ],
                            [
                                'name' => 'Anjing Golden Retriever Puppy',
                                'base_price' => 3500000,
                                'stock_qty' => 2,
                                'description' => 'Puppy Golden Retriever kualitas bagus, tulang kasar, aktif dan ramah.',
                                'detail' => [
                                    'breed' => 'Golden Retriever',
                                    'age_months' => 2,
                                    'gender' => 'male',
                                    'vaccination_status' => 'partially vaccinated',
                                    'weight_kg' => 5.2,
                                    'health_history' => 'Vaksin parvo dosis 1, obat cacing rutin.',
                                ],
                            ],
                            [
                                'name' => 'Kelinci Holland Lop',
                                'base_price' => 450000,
                                'stock_qty' => 5,
                                'description' => 'Kelinci hias Holland Lop telinga turun, lucu dan jinak.',
                                'detail' => [
                                    'breed' => 'Holland Lop',
                                    'age_months' => 4,
                                    'gender' => 'male',
                                    'vaccination_status' => 'not vaccinated',
                                    'weight_kg' => 1.2,
                                    'health_history' => 'Sehat, makan lahap (hay & pelet).',
                                ],
                            ],
                        ],
                        'goods' => [],
                    ],
                    'Pakan & Aksesori' => [
                        'sort_order' => 2,
                        'animals' => [],
                        'goods' => [
                            [
                                'name' => 'Royal Canin Cat Adult 2kg',
                                'base_price' => 285000,
                                'stock_qty' => 50,
                                'description' => 'Makanan kering kucing dewasa untuk kesehatan bulu dan saluran cerna.',
                                'detail' => [
                                    'weight_gram' => 2000,
                                    'dimension_cm' => '30x20x10',
                                    'expired_date' => now()->addYears(2)->format('Y-m-d'),
                                ],
                            ],
                            [
                                'name' => 'Shampoo Kucing & Anjing Flea & Tick 250ml',
                                'base_price' => 65000,
                                'stock_qty' => 30,
                                'description' => 'Shampo anti kutu dan jamur dengan aroma lembut.',
                                'detail' => [
                                    'weight_gram' => 300,
                                    'dimension_cm' => '18x6x6',
                                    'expired_date' => now()->addYears(3)->format('Y-m-d'),
                                ],
                            ],
                            [
                                'name' => 'Kandang Besi Lipat Size L',
                                'base_price' => 250000,
                                'stock_qty' => 15,
                                'description' => 'Kandang lipat besi tebal anti karat, dimensi 75x45x50 cm.',
                                'detail' => [
                                    'weight_gram' => 4500,
                                    'dimension_cm' => '75x45x50',
                                    'expired_date' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'PAW' => [
                'slug' => 'paw-care',
                'name' => 'Paw Care & Pets',
                'tax_rate' => 0.1000,
                'service_fee_rate' => 0.0200,
                'latitude' => -6.90000000,
                'longitude' => 107.60000000,
                'max_delivery_radius_km' => 10.00,
                'balance' => 2000000,
                'commission_rate' => 0.1500,
                'categories' => [
                    'Hewan Eksotis & Burung' => [
                        'sort_order' => 1,
                        'animals' => [
                            [
                                'name' => 'Burung Lovebird Biola',
                                'base_price' => 350000,
                                'stock_qty' => 4,
                                'description' => 'Lovebird mutasi warna cerah, suara ngekek aktif.',
                                'detail' => [
                                    'breed' => 'Lovebird Biola',
                                    'age_months' => 6,
                                    'gender' => 'female',
                                    'vaccination_status' => 'not vaccinated',
                                    'weight_kg' => 0.06,
                                    'health_history' => 'Sehat dan lincah.',
                                ],
                            ],
                        ],
                        'goods' => [],
                    ],
                    'Kebutuhan Perawatan' => [
                        'sort_order' => 2,
                        'animals' => [],
                        'goods' => [
                            [
                                'name' => 'Pasir Kucing Gumpal Wangi 10L',
                                'base_price' => 75000,
                                'stock_qty' => 40,
                                'description' => 'Pasir bentonite wangi apel cepat menggumpal dan mengontrol bau.',
                                'detail' => [
                                    'weight_gram' => 8000,
                                    'dimension_cm' => '40x25x10',
                                    'expired_date' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tenants = [];
        foreach ($tenantsBlueprint as $code => $spec) {
            $tenant = tap(Tenant::withTrashed()->firstOrNew(['code' => $code]))
                ->forceFill([
                    'slug' => $spec['slug'],
                    'name' => $spec['name'],
                    'tax_rate' => $spec['tax_rate'],
                    'service_fee_rate' => $spec['service_fee_rate'],
                    'status' => 'active',
                    'latitude' => $spec['latitude'],
                    'longitude' => $spec['longitude'],
                    'max_delivery_radius_km' => $spec['max_delivery_radius_km'],
                    'deleted_at' => null,
                ]);
            $tenant->save();
            $tenants[$code] = $tenant;

            tap(TenantBalance::firstOrNew(['tenant_id' => $tenant->id]))
                ->forceFill([
                    'available_amount' => $spec['balance'],
                    'held_amount' => 0,
                ])->save();

            tap(CommissionScheme::firstOrNew([
                'tenant_id' => $tenant->id,
                'valid_from' => now()->startOfYear(),
            ]))->forceFill([
                'commission_rate' => $spec['commission_rate'],
                'valid_to' => null,
            ])->save();

            foreach ($spec['categories'] as $categoryName => $catData) {
                $category = tap(ProductCategory::firstOrNew([
                    'tenant_id' => $tenant->id,
                    'name' => $categoryName,
                ]))->forceFill([
                    'sort_order' => $catData['sort_order'],
                    'is_active' => true,
                ]);
                $category->save();

                foreach ($catData['animals'] as $item) {
                    $product = tap(Product::firstOrNew([
                        'tenant_id' => $tenant->id,
                        'name' => $item['name'],
                    ]))->forceFill([
                        'category_id' => $category->id,
                        'base_price' => $item['base_price'],
                        'stock_qty' => $item['stock_qty'],
                        'is_available' => true,
                        'description' => $item['description'],
                        'photo_path' => null,
                    ]);
                    $product->save();

                    tap(ProductAnimal::firstOrNew(['product_id' => $product->id]))
                        ->forceFill([
                            'tenant_id' => $tenant->id,
                            'breed' => $item['detail']['breed'],
                            'age_months' => $item['detail']['age_months'],
                            'gender' => $item['detail']['gender'],
                            'vaccination_status' => $item['detail']['vaccination_status'],
                            'weight_kg' => $item['detail']['weight_kg'],
                            'health_history' => $item['detail']['health_history'],
                        ])->save();
                }

                foreach ($catData['goods'] as $item) {
                    $product = tap(Product::firstOrNew([
                        'tenant_id' => $tenant->id,
                        'name' => $item['name'],
                    ]))->forceFill([
                        'category_id' => $category->id,
                        'base_price' => $item['base_price'],
                        'stock_qty' => $item['stock_qty'],
                        'is_available' => true,
                        'description' => $item['description'],
                        'photo_path' => null,
                    ]);
                    $product->save();

                    tap(ProductGood::firstOrNew(['product_id' => $product->id]))
                        ->forceFill([
                            'tenant_id' => $tenant->id,
                            'weight_gram' => $item['detail']['weight_gram'],
                            'dimension_cm' => $item['detail']['dimension_cm'],
                            'expired_date' => $item['detail']['expired_date'],
                        ])->save();
                }
            }
        }

        // Operator demo -> tenant DEMO (peran operator).
        $operator = User::where('email', 'tenant@petmarket.test')->first();
        if ($operator !== null) {
            UserTenantRole::firstOrCreate([
                'user_id' => $operator->id,
                'tenant_id' => $tenants['DEMO']->id,
                'role' => 'operator',
            ]);
        }
    }
}
