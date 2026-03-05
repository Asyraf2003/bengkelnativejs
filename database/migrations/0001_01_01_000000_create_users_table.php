<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('username')->unique();
            $table->string('password_hash')->nullable();

            $table->enum('role', ['admin', 'cashier'])->index();
            $table->boolean('is_active')->default(true)->index();

            // optional, aman untuk session "remember me" (walau kita tidak pakai dulu)
            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
