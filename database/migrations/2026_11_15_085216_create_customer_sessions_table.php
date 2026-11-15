<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_sessions', function (Blueprint $table) {
            $table->id(); // Diperbaiki: tambahkan $table-> di depan id()
            $table->foreignId('canteen_id')->nullable();
            $table->foreignId('dining_table_id')->nullable();
            $table->string('session_token_hash', 64)->unique();
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_sessions');
    }
};
