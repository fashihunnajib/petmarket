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
        Schema::create('penarikan_danas', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('tenant_id'); // Foreign key ke tenants
            $table->decimal('nominal', 12, 2);
            $table->string('status')->default('Pending'); // Pending, Terverifikasi
            $table->dateTime('waktu_pengajuan');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penarikan_danas');
    }
};
