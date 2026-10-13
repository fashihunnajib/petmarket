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
       Schema::create('item_keranjangs', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('keranjang_id'); // Foreign key ke keranjangs
        $table->string('produk_id');     // Foreign key ke produks
        $table->integer('kuantitas');
        $table->timestamps();

        $table->foreign('keranjang_id')->references('id')->on('keranjangs')->onDelete('cascade');
        $table->foreign('produk_id')->references('id')->on('produks')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_keranjangs');
    }
};
