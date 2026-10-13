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
        Schema::create('pengirimans', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('pesanan_id');
        $table->string('tipe_kurir'); // Contoh: Kurir Toko/Ketemuan atau Reguler
        $table->string('status_pengiriman')->default('Menunggu');
        $table->timestamps();

        $table->foreign('pesanan_id')->references('id')->on('pesanans')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengirimans');
    }
};
