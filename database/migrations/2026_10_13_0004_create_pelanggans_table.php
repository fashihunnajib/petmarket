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
    Schema::create('pelanggans', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('pengguna_id');
        $table->text('alamat');
        $table->float('latitude');
        $table->float('longitude');
        $table->timestamps();

        $table->foreign('pengguna_id')->references('id')->on('penggunas')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};
