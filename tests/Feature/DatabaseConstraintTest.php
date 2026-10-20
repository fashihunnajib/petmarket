<?php

namespace Tests\Feature;

use App\Models\CommissionScheme;
use App\Models\Product;
use App\Models\ProductAnimal;
use App\Models\ProductCategory;
use App\Models\ProductGood;
use App\Models\Tenant;
use App\Models\TenantBalance;
use App\Models\User;
use App\Models\UserTenantRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Constraint diuji pada database nyata — komposit FK lintas-tenant,
 * unique constraint, validasi tipe data moneter, dan idempotensi stock/order.
 */
class DatabaseConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_cannot_reference_another_tenants_category(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();
        $categoryB = ProductCategory::factory()->create(['tenant_id' => $b->id]);

        $this->expectException(QueryException::class);
        Product::factory()->create([
            'tenant_id' => $a->id,             // tenant A
            'category_id' => $categoryB->id,   // kategori tenant B -> composite FK gagal
        ]);
    }

    public function test_product_animal_cannot_reference_another_tenants_product(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();
        $productB = Product::factory()->create(['tenant_id' => $b->id]);

        $this->expectException(QueryException::class);
        ProductAnimal::factory()->create([
            'tenant_id' => $a->id,             // tenant A
            'product_id' => $productB->id,     // produk milik tenant B -> composite FK gagal
        ]);
    }

    public function test_product_good_cannot_reference_another_tenants_product(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();
        $productB = Product::factory()->create(['tenant_id' => $b->id]);

        $this->expectException(QueryException::class);
        ProductGood::factory()->create([
            'tenant_id' => $a->id,             // tenant A
            'product_id' => $productB->id,     // produk milik tenant B -> composite FK gagal
        ]);
    }

    public function test_duplicate_tenant_code_is_rejected(): void
    {
        Tenant::factory()->create(['code' => 'DUP01']);

        $this->expectException(QueryException::class);
        Tenant::factory()->create(['code' => 'DUP01']);
    }

    public function test_duplicate_tenant_slug_is_rejected(): void
    {
        Tenant::factory()->create(['slug' => 'pet-demo']);

        $this->expectException(QueryException::class);
        Tenant::factory()->create(['slug' => 'pet-demo']);
    }

    public function test_duplicate_tenant_balance_for_same_tenant_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        TenantBalance::create(['tenant_id' => $tenant->id, 'available_amount' => 0, 'held_amount' => 0]);

        $this->expectException(QueryException::class);
        TenantBalance::create(['tenant_id' => $tenant->id, 'available_amount' => 1000, 'held_amount' => 0]);
    }

    public function test_duplicate_user_tenant_role_is_rejected(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        UserTenantRole::create(['user_id' => $user->id, 'tenant_id' => $tenant->id, 'role' => 'operator']);

        $this->expectException(QueryException::class);
        UserTenantRole::create(['user_id' => $user->id, 'tenant_id' => $tenant->id, 'role' => 'operator']);
    }

    public function test_stock_movement_idempotency_key_is_unique(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);

        $table = Schema::hasTable('product_stock_movements') ? 'product_stock_movements' : 'menu_stock_movements';
        $itemColumn = Schema::hasColumn($table, 'product_id') ? 'product_id' : 'menu_id';

        $row = fn () => [
            'tenant_id' => $tenant->id,
            $itemColumn => $product->id,
            'idempotency_key' => 'stk-key-1',
            'type' => 'sale',
            'quantity_delta' => -1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table($table)->insert($row());

        $this->expectException(QueryException::class);
        DB::table($table)->insert($row());
    }

    public function test_orders_has_no_tenant_id_and_supports_multiple_tenant_orders(): void
    {
        $this->assertFalse(Schema::hasColumn('orders', 'tenant_id'), 'orders TIDAK boleh punya tenant_id');

        if (Schema::hasTable('orders')) {
            $user = User::factory()->create();
            $a = Tenant::factory()->create();
            $b = Tenant::factory()->create();
            $ca = CommissionScheme::factory()->create(['tenant_id' => $a->id]);
            $cb = CommissionScheme::factory()->create(['tenant_id' => $b->id]);

            $orderId = DB::table('orders')->insertGetId([
                'public_id' => (string) Str::uuid(),
                'order_number' => 'ORD-1',
                'checkout_key' => 'ck-1',
                'status' => 'pending',
                'grand_total_amount' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([[$a, $ca], [$b, $cb]] as [$t, $c]) {
                DB::table('tenant_orders')->insert([
                    'order_id' => $orderId,
                    'tenant_id' => $t->id,
                    'user_id' => $user->id,
                    'commission_id' => $c->id,
                    'checkout_key' => 'ck-'.$t->id,
                    'order_number' => 'TO-'.$t->id,
                    'public_id' => (string) Str::uuid(),
                    'status' => 'pending_payment',
                    'commission_rate_snapshot' => 0.1500,
                    'subtotal_amount' => 25000,
                    'commission_amount' => 3750,
                    'net_amount' => 21250,
                    'shipping_address' => 'Jl. Test No. 1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->assertSame(2, DB::table('tenant_orders')->where('order_id', $orderId)->count());
        } else {
            $user = User::factory()->create();
            $a = Tenant::factory()->create();
            $b = Tenant::factory()->create();
            $ca = CommissionScheme::factory()->create(['tenant_id' => $a->id]);
            $cb = CommissionScheme::factory()->create(['tenant_id' => $b->id]);

            foreach ([[$a, $ca, 'ck-1'], [$b, $cb, 'ck-2']] as [$t, $c, $k]) {
                DB::table('tenant_orders')->insert([
                    'public_id' => (string) Str::uuid(),
                    'order_number' => 'ORD-'.$t->id,
                    'tenant_id' => $t->id,
                    'user_id' => $user->id,
                    'commission_id' => $c->id,
                    'checkout_key' => $k,
                    'status' => 'pending_payment',
                    'commission_rate_snapshot' => 0.1500,
                    'subtotal_amount' => 25000,
                    'commission_amount' => 3750,
                    'net_amount' => 21250,
                    'shipping_address' => 'Jl. Test No. 1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->assertSame(2, DB::table('tenant_orders')->where('user_id', $user->id)->count());
        }
    }

    public function test_money_columns_cast_to_integer(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 25000]);

        $this->assertIsInt($product->refresh()->base_price);
        $this->assertSame(25000, $product->base_price);
    }
}
