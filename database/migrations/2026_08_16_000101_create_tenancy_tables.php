<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Domain A — Tenancy, pengguna, akses (ERD Fase 0).
 * Uang = unsignedBigInteger (Rupiah). Rate = decimal exact (bukan float).
 * Composite UNIQUE (tenant_id, id) menopang composite FK anti lintas-tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('slug', 100)->unique();
            $table->string('name', 120);
            $table->decimal('tax_rate', 6, 4)->default(0);
            $table->decimal('service_fee_rate', 6, 4)->default(0);
            $table->string('status', 20)->default('pending'); // pending|active|suspended|inactive
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('max_delivery_radius_km', 5, 2)->default(0.00);
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
        });

        Schema::create('tenant_balances', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('available_amount')->default(0);
            $table->unsignedBigInteger('held_amount')->default(0);
            $table->timestamps(6);
        });
        // CHECK saldo tak boleh negatif (unsigned sudah menjamin >=0; CHECK eksplisit untuk audit).
        DB::statement('ALTER TABLE tenant_balances ADD CONSTRAINT chk_tenant_balance_nonneg CHECK (available_amount >= 0 AND held_amount >= 0)');

        Schema::create('tenant_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('account_number_cipher');      // terenkripsi (cast encrypted)
            $table->string('account_last4', 4);
            $table->string('bank_code', 20);
            $table->string('account_holder', 120);
            $table->string('status', 20)->default('unverified'); // unverified|verified|rejected
            $table->boolean('is_primary')->default(false);
            $table->timestamps(6);

            $table->unique(['tenant_id', 'id']);
        });

        // users sudah ada (Modul 1/2). Lengkapi kolom identitas dari ERD.
        Schema::table('users', function (Blueprint $table): void {
            $table->text('address')->nullable()->after('email');
            $table->string('phone_e164', 20)->nullable()->unique()->after('address');
            $table->decimal('latitude', 10, 8)->nullable()->after('phone_e164');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::create('user_tenant_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30); // owner|operator|cashier -> sumber TenantContext
            $table->timestamps(6);

            $table->unique(['user_id', 'tenant_id', 'role']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tenant_roles');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['phone_e164']);
            $table->dropColumn('address');
            $table->dropColumn('phone_e164');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });
        Schema::dropIfExists('tenant_bank_accounts');
        Schema::dropIfExists('tenant_balances');
        Schema::dropIfExists('tenants');
    }
};
