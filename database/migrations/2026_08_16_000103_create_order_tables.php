<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain C — Order, Logistik & Snapshot (PetMarket Platform).
 * Pesanan dipecah per tenant saat checkout (FR-CUS-03).
 * Isolasi data lintas-tenant ditegakkan DB via Composite FK (tenant_id, id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id');
            $table->string('order_number', 30);
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('commission_id')->nullable();
            $table->string('checkout_key', 100); // idempotency checkout
            $table->string('status', 30)->default('pending_payment'); // pending_payment|new_order|processing|in_transit|completed|cancelled|expired
            $table->decimal('commission_rate_snapshot', 6, 4);
            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('service_fee_amount')->default(0);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->unsignedBigInteger('net_amount')->default(0);
            $table->unsignedBigInteger('grand_total_amount')->default(0);
            $table->text('shipping_address');
            $table->decimal('shipping_latitude', 10, 8)->nullable();
            $table->decimal('shipping_longitude', 11, 8)->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->timestampTz('placed_at', 6)->nullable();
            $table->timestamps(6);

            $table->unique('public_id');
            $table->unique('order_number');
            $table->unique('checkout_key');
            $table->unique(['tenant_id', 'id']); // target composite FK order_items
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->foreign(['tenant_id', 'commission_id'])
                ->references(['tenant_id', 'id'])->on('commission_schemes')->restrictOnDelete();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('tenant_order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('name_snapshot', 120);
            $table->enum('type_snapshot', ['animal', 'good']); // animal|good
            $table->unsignedBigInteger('unit_price_snapshot');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps(6);

            $table->unique(['tenant_id', 'id']); // target composite FK modifiers
            $table->index(['tenant_id', 'tenant_order_id']);
            $table->foreign(['tenant_id', 'tenant_order_id'])
                ->references(['tenant_id', 'id'])->on('tenant_orders')->cascadeOnDelete();
            $table->foreign(['tenant_id', 'product_id'])
                ->references(['tenant_id', 'id'])->on('products')->restrictOnDelete();
        });

        Schema::create('product_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->string('idempotency_key', 100);
            $table->string('type', 20); // sale|restock|adjustment
            $table->integer('quantity_delta');
            $table->timestamps(6);

            $table->unique('idempotency_key');
            $table->index(['tenant_id', 'product_id']);
            $table->foreign(['tenant_id', 'product_id'])
                ->references(['tenant_id', 'id'])->on('products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_movements');
        Schema::dropIfExists('order_deliveries');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('tenant_orders');
    }
};
