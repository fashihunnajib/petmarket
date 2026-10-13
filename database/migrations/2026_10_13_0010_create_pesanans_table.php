<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanans', function (Blueprint $table) {
            $table->string('id')->primary();
            
            // Tambahkan ->index() secara eksplisit sebelum foreign key
            $table->string('pelanggan_id')->index();
            $table->string('tenant_id')->index();
            
            $table->enum('status_pesanan', [
                'Menunggu Pembayaran', 
                'Pesanan Baru', 
                'Diproses', 
                'Dalam Perjalanan', 
                'Selesai'
            ])->default('Menunggu Pembayaran');
            
            $table->decimal('total_belanja');
            $table->timestamps();

            // Relasi Foreign Key
            $table->foreign('pelanggan_id')->references('id')->on('pelanggans')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanans');
    }
};