<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain D — Integrasi pengiriman & logistik (ERD Fase 0).
 * Isolasi lintas-tenant ditegakkan DB: composite FK (tenant_id, x_id) -> parent(tenant_id, id).
 * Global scope (Modul 4) melengkapi, tetapi constraint DB tetap benteng terakhir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_order_id');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Opsi Kurir: Kurir Toko/Ketemuan (Khusus Hewan) vs Ekspedisi Reguler (Barang Fisik)
            $table->enum('delivery_type', ['seller_courier_meetup', 'regular_expedition']);
            $table->string('expedition_name', 50)->nullable(); // Misal: JNE, J&T (Jika barang fisik)
            $table->string('tracking_number', 100)->nullable(); // Nomor Resi
            $table->decimal('distance_km', 6, 2)->nullable(); // Jarak kalkulasi geo-fencing (FR-CUS-01)
            $table->string('delivery_status', 30)->default('preparing'); // preparing | on_delivery | delivered | failed
            $table->text('notes')->nullable(); // Catatan titik temu / petunjuk pengiriman
            $table->timestamps(6);

            $table->unique(['tenant_id', 'tenant_order_id']); // Relasi 1:1 dengan tenant_orders
            $table->foreign(['tenant_id', 'tenant_order_id'])
                ->references(['tenant_id', 'id'])->on('tenant_orders')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_deliveries');
    }
};
