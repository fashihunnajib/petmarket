<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('pengguna_id'); // Foreign Key ke tabel penggunas
            $table->string('nama_toko');
            $table->float('latitude');
            $table->float('longitude');
            $table->float('max_radius_km');
            $table->decimal('saldo', 12, 2)->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Definisi Foreign Key
            $table->foreign('pengguna_id')->references('id')->on('penggunas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
