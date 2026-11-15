<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('checkout_key', 100)->unique();
            $table->string('status', 30)->default('awaiting_payment'); // awaiting_payment|paid|cancelled|expired
            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->unsignedBigInteger('service_fee_amount')->default(0);
            $table->unsignedBigInteger('grand_total_amount')->default(0);
            $table->text('shipping_address')->nullable();
            $table->decimal('shipping_latitude', 10, 8)->nullable();
            $table->decimal('shipping_longitude', 11, 8)->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->timestampTz('placed_at', 6)->nullable();
            $table->timestamps(6);

            $table->index(['user_id', 'status']);
        });

        Schema::table('tenant_orders', function (Blueprint $table): void {
            $table->foreignId('order_id')
                ->nullable()
                ->after('order_number')
                ->constrained('orders')
                ->cascadeOnDelete();
        });

        // Lepas relasi lama tenant_order_id di payments lalu ganti ke order_id (aman untuk SQLite & MySQL)
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['tenant_order_id']);
            $table->dropUnique(['tenant_order_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('tenant_order_id');
            $table->foreignId('order_id')
                ->after('id')
                ->unique()
                ->constrained('orders')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropUnique(['order_id']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('order_id');
            $table->foreignId('tenant_order_id')
                ->after('id')
                ->unique()
                ->constrained('tenant_orders')
                ->restrictOnDelete();
        });

        Schema::table('tenant_orders', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });

        Schema::dropIfExists('orders');
    }
};
