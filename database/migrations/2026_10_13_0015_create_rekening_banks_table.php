<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekening_banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nama_bank'); // Contoh: BCA, Mandiri, DANA, OVO
            $table->string('nomor_rekening');
            $table->string('nama_pemilik');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Tambahkan relasi rekening_bank_id ke tabel penarikan_danas yang sudah ada
        Schema::table('penarikan_danas', function (Blueprint $table) {
            $table->foreignUuid('rekening_bank_id')->nullable()->after('tenant_id')->constrained('rekening_banks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penarikan_danas', function (Blueprint $table) {
            $table->dropForeign(['rekening_bank_id']);
            $table->dropColumn('rekening_bank_id');
        });

        Schema::dropIfExists('rekening_banks');
    }
};
