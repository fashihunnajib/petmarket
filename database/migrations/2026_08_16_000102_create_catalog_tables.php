<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain B — Katalog & konfigurasi tenant (ERD Fase 0).
 * Isolasi lintas-tenant ditegakkan DB: composite FK (tenant_id, x_id) -> parent(tenant_id, id).
 * Global scope (Modul 4) melengkapi, tetapi constraint DB tetap benteng terakhir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_operating_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Minggu .. 6=Sabtu
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps(6);

            $table->unique(['tenant_id', 'day_of_week']);
        });

        Schema::create('commission_schemes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('commission_rate', 6, 4); // exact, mis. 0.1500 = 15%
            $table->timestampTz('valid_from', 6);
            $table->timestampTz('valid_to', 6)->nullable(); // null = masih berlaku
            $table->timestamps(6);

            $table->unique(['tenant_id', 'id']); // target FK snapshot komisi
            $table->index(['tenant_id', 'valid_from']);
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps(6);

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'id']); // target composite FK
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('category_id');
            $table->string('name', 120);
            $table->enum('type', ['animal', 'good']);
            $table->unsignedBigInteger('base_price'); // Rupiah
            $table->integer('stock_qty')->default(0);
            $table->boolean('is_available')->default(true);
            $table->text('description')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->timestamps(6);

            $table->unique(['tenant_id', 'id']); // target composite FK (order_items, pivot)
            $table->index(['tenant_id', 'category_id', 'is_available']);
            // Kategori harus milik tenant yang sama.
            $table->foreign(['tenant_id', 'category_id'])
                ->references(['tenant_id', 'id'])->on('product_categories')->restrictOnDelete();
        });

        Schema::create('product_animals', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id')->primary();
            $table->foreignId('tenant_id');
            $table->string('breed', 100);
            $table->unsignedInteger('age_months');
            $table->enum('gender', ['male', 'female']);
            $table->string('vaccination_status', 50); // mis. "vaccinated", "not vaccinated" wajib.
            $table->string('weight_kg', 20)->nullable(); // opsional, untuk hewan peliharaan
            $table->text('health_history')->nullable();
            $table->timestamps(6);

            // Composite FK ke tabel products untuk memastikan isolasi tenant
            $table->foreign(['tenant_id', 'product_id'])
                  ->references(['tenant_id', 'id'])->on('products')->cascadeOnDelete();
        });

        Schema::create('product_goods', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id')->primary();
            $table->foreignId('tenant_id');
            $table->unsignedMediumInteger('weight_gram'); // Wajib untuk hitung ongkir ekspedisi
            $table->string('dimension_cm', 50)->nullable(); // Format: PxLxT
            $table->date('expired_date')->nullable();
            $table->timestamps(6);

            // Composite FK ke tabel products
            $table->foreign(['tenant_id', 'product_id'])
                  ->references(['tenant_id', 'id'])->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_goods');
        Schema::dropIfExists('product_animals');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('commission_schemes');
        Schema::dropIfExists('tenant_operating_hours');
    }
};
