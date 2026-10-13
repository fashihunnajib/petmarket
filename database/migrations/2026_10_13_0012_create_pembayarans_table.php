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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('pesanan_id');
            $table->string('qris_id')->nullable();
            $table->decimal('total_bayar', 12, 2);
            $table->decimal('pendapatan_tenant', 12, 2);
            $table->decimal('komisi_platform', 12, 2);
            $table->string('status_pembayaran')->default('Pending');
            $table->timestamps();

            $table->foreign('pesanan_id')->references('id')->on('pesanans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
