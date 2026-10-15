<?php

namespace Tests\Feature;

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
use Tests\TestCase;

/**
 * Constraint diuji pada database nyata — komposit FK lintas-tenant,
 * unique constraint, dan validasi tipe data moneter.
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

    public function test_money_columns_cast_to_integer(): void
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'base_price' => 25000]);

        $this->assertIsInt($product->refresh()->base_price);
        $this->assertSame(25000, $product->base_price);
    }
}
