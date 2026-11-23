<?php

namespace Tests\Feature;

use App\Models\CustomerSession;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantOrder;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Modules\Ordering\Data\CheckoutDetails;
use App\Modules\Ordering\Exceptions\PreOrderException;
use App\Modules\Ordering\Services\CartService;
use App\Modules\Ordering\Services\CheckoutService;
use App\Modules\Ordering\Services\PreOrderScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PreOrderUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }

    public function test_scheduler_validates_slots_and_suggests_nearest_when_full(): void
    {
        $now = CarbonImmutable::parse('2026-11-23 09:00:00', 'Asia/Jakarta');
        $this->travelTo($now);

        $tenant = Tenant::factory()->create([
            'name' => 'PetGroom & Mart',
            'status' => 'active',
            'pre_order_enabled' => true,
            'pre_order_slot_capacity' => 1,
        ]);

        DB::table('tenant_operating_hours')->insert([
            'tenant_id' => $tenant->id,
            'day_of_week' => $now->dayOfWeek,
            'open_time' => '08:00:00',
            'close_time' => '20:00:00',
            'is_closed' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 50000,
            'stock_qty' => 10,
            'is_available' => true,
        ]);

        $tenants = Tenant::query()->whereKey($tenant->id)->get();
        $scheduler = app(PreOrderScheduler::class);

        $slot10 = CarbonImmutable::parse('2026-11-23 10:00:00', 'Asia/Jakarta');
        $scheduler->assertSchedulable($tenants, $slot10, $now);

        $session1 = CustomerSession::create([
            'tenant_id' => $tenant->id,
            'session_token_hash' => hash('sha256', 'preorder-token-1'),
            'status' => 'active',
            'expires_at' => $now->addHours(4),
        ]);
        app(CartService::class)->add($session1, $product->id, 1);

        $result = app(CheckoutService::class)->checkout(
            $session1,
            (string) Str::uuid(),
            $slot10,
            new CheckoutDetails(CheckoutDetails::PICKUP, 'Raka', '628123456781')
        );

        $this->assertDatabaseHas('tenant_orders', [
            'order_id' => $result->order->id,
            'tenant_id' => $tenant->id,
            'status' => 'scheduled',
        ]);

        try {
            $scheduler->assertSchedulable($tenants, $slot10, $now);
            $this->fail('Seharusnya PreOrderException dilempar saat slot penuh.');
        } catch (PreOrderException $e) {
            $this->assertNotEmpty($e->alternatives);
        }
    }

    public function test_release_scheduled_command_only_releases_due_paid_orders(): void
    {
        $now = CarbonImmutable::parse('2026-11-23 10:00:00', 'Asia/Jakarta');
        $this->travelTo($now);

        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'pre_order_enabled' => true,
            'pre_order_slot_capacity' => 5,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'base_price' => 40000,
            'stock_qty' => 10,
            'is_available' => true,
        ]);

        $session = CustomerSession::create([
            'tenant_id' => $tenant->id,
            'session_token_hash' => hash('sha256', 'preorder-release-token'),
            'status' => 'active',
            'expires_at' => $now->addHours(4),
        ]);

        app(CartService::class)->add($session, $product->id, 1);

        $scheduledAt = $now->addMinutes(30);
        $checkout = app(CheckoutService::class)->checkout(
            $session,
            (string) Str::uuid(),
            $scheduledAt,
            new CheckoutDetails(CheckoutDetails::PICKUP, 'Tari', '628123456782')
        );

        /** @var TenantOrder $tenantOrder */
        $tenantOrder = TenantOrder::query()->withoutGlobalScope('tenant')->where('order_id', $checkout->order->id)->firstOrFail();
        DB::table('tenant_orders')
            ->where('id', $tenantOrder->id)
            ->update(['release_at' => now()->subMinute()->format('Y-m-d H:i:s')]);

        $this->artisan('ordering:release-scheduled')->assertSuccessful();
        $this->assertSame('scheduled', $tenantOrder->fresh()->status);

        DB::table('orders')
            ->where('id', $checkout->order->id)
            ->update(['status' => 'paid']);

        $this->artisan('ordering:release-scheduled')->assertSuccessful();
        $this->assertSame('pending', $tenantOrder->fresh()->status);
    }

    public function test_tenant_staff_can_update_pre_order_settings(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'pre_order_enabled' => false,
            'pre_order_slot_capacity' => 5,
        ]);

        UserTenantRole::query()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        Livewire::actingAs($user)
            ->test('ordering::pre-order-settings', ['tenantId' => $tenant->id])
            ->set('enabled', true)
            ->set('slotCapacity', 8)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'pre_order_enabled' => 1,
            'pre_order_slot_capacity' => 8,
        ]);
    }
}
