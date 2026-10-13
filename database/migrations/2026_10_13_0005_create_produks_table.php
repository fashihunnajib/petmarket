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
        Schema::create('produks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('tenant_id'); // Foreign key ke tenants
            $table->string('nama_produk');
            $table->string('slug')->unique(); // Untuk URL ramah SEO
            $table->enum('tipe', ['Hewan Hidup', 'Barang Fisik']);
            $table->decimal('harga', 12, 2);
            $table->integer('stok');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
