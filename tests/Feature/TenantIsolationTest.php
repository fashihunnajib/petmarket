<?php

namespace Tests\Feature;

use App\Jobs\CountTenantProducts;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Modules\Catalog\Services\PublicCatalogQuery;
use App\Policies\ProductPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriks isolasi tenant (allow/deny) A vs B: global scope, scoped binding, policy,
 * public bypass terkontrol, dan context pada job. Dijalankan di MariaDB nyata.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Tenant, 1: Collection<int, Product>} */
    private function tenantWithProducts(int $count, ?Tenant $tenant = null, string $status = 'active'): array
    {
        $tenant ??= Tenant::factory()->create(['status' => $status]);
        $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);
        $products = Product::factory()->count($count)->create(['tenant_id' => $tenant->id, 'category_id' => $category->id]);

        return [$tenant, $products];
    }

    private function memberOf(Tenant $tenant): User
    {
        $user = User::factory()->create(['role' => 'tenant', 'status' => 'active', 'email_verified_at' => now()]);
        UserTenantRole::create(['user_id' => $user->id, 'tenant_id' => $tenant->id, 'role' => 'operator']);

        return $user;
    }

    public function test_global_scope_returns_only_active_tenant_rows(): void
    {
        [$a] = $this->tenantWithProducts(2);
        $this->tenantWithProducts(3); // tenant B

        app(TenantContext::class)->set($a);
        $this->assertSame(2, Product::query()->count());
        $this->assertTrue(Product::query()->get()->every(fn (Product $p) => $p->tenant_id === $a->id));
    }

    public function test_without_context_scope_is_not_applied(): void
    {
        $this->tenantWithProducts(2);
        $this->tenantWithProducts(3);

        // Tanpa context, scope tidak memfilter (alur internal SELALU mengisi context).
        $this->assertSame(5, Product::query()->count());
    }

    public function test_context_is_reset_between_requests(): void
    {
        [$a] = $this->tenantWithProducts(1);
        app(TenantContext::class)->set($a);
        $this->assertTrue(app(TenantContext::class)->has());

        // Simulasi lifecycle request/job berikutnya.
        $this->app->forgetScopedInstances();
        $this->assertFalse(app(TenantContext::class)->has(), 'TenantContext bocor antar request/job');
    }

    public function test_tenant_id_is_autofilled_on_create_within_context(): void
    {
        [$a] = $this->tenantWithProducts(0);
        app(TenantContext::class)->set($a);

        $category = ProductCategory::create(['name' => 'Auto Fill']);
        $this->assertSame($a->id, $category->tenant_id);
    }

    public function test_product_index_returns_only_own_tenant(): void
    {
        [$a, $productsA] = $this->tenantWithProducts(2);
        $this->tenantWithProducts(3); // B

        $this->actingAs($this->memberOf($a));
        $response = $this->getJson(route('tenant.products.index', ['tenant' => $a->slug]));

        $response->assertOk();
        $this->assertCount(2, $response->json('products'));
    }

    public function test_scoped_binding_blocks_cross_tenant_product(): void
    {
        [$a] = $this->tenantWithProducts(1);
        [, $productsB] = $this->tenantWithProducts(1);
        $productB = $productsB->first();

        $this->actingAs($this->memberOf($a));
        // Product tenant B diakses lewat route tenant A -> tidak ditemukan (scoped binding + scope).
        $this->getJson(route('tenant.products.show', ['tenant' => $a->slug, 'product' => $productB->id]))
            ->assertNotFound();
    }

    public function test_member_can_toggle_own_product(): void
    {
        [$a, $productsA] = $this->tenantWithProducts(1);
        $productA = $productsA->first();

        $this->actingAs($this->memberOf($a));
        $this->patchJson(route('tenant.products.update', ['tenant' => $a->slug, 'product' => $productA->id]), [
            'is_available' => false,
        ])->assertOk()->assertJson(['ok' => true, 'is_available' => false]);

        $this->assertFalse($productA->refresh()->is_available);
    }

    public function test_policy_denies_cross_tenant_update(): void
    {
        [$a] = $this->tenantWithProducts(0);
        [, $productsB] = $this->tenantWithProducts(1);
        $productB = $productsB->first();

        app(TenantContext::class)->set($a); // aktif = A
        $this->assertFalse((new ProductPolicy)->update(new User, $productB), 'Policy harus menolak product tenant lain');
    }

    public function test_public_catalog_query_no_inactive_or_unsellable_leak(): void
    {
        [$tenantA, $productsA] = $this->tenantWithProducts(2);                      // 2 produk aktif & sellable
        [$tenantB] = $this->tenantWithProducts(1);                                  // 1 produk aktif & sellable (tenant lain)
        $this->tenantWithProducts(1, status: 'suspended');                          // tenant suspended -> tidak tampil

        // Produk tidak tersedia (is_available = false)
        Product::factory()->create([
            'tenant_id' => $tenantA->id,
            'category_id' => $productsA->first()->category_id,
            'is_available' => false,
            'stock_qty' => 10,
        ]);

        // Produk stok habis (stock_qty = 0)
        Product::factory()->create([
            'tenant_id' => $tenantA->id,
            'category_id' => $productsA->first()->category_id,
            'is_available' => true,
            'stock_qty' => 0,
        ]);

        $paginator = app(PublicCatalogQuery::class)->browse();

        // Total produk yang tampil di etalase publik: 2 dari Tenant A + 1 dari Tenant B = 3 produk
        $this->assertSame(3, $paginator->total(), 'Katalog publik hanya menampilkan produk sellable dari tenant aktif');
    }

    public function test_job_sets_and_clears_context_per_tenant(): void
    {
        [$a] = $this->tenantWithProducts(2);
        [$b] = $this->tenantWithProducts(3);

        $jobA = new CountTenantProducts($a->id);
        $jobA->handle(app(TenantContext::class));
        $this->assertSame(2, $jobA->result);
        $this->assertFalse(app(TenantContext::class)->has(), 'Context harus dibersihkan setelah job');

        $jobB = new CountTenantProducts($b->id);
        $jobB->handle(app(TenantContext::class));
        $this->assertSame(3, $jobB->result); // tidak terkontaminasi tenant A
    }
}
