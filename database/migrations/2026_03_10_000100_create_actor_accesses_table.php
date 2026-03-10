<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actor_accesses', function (Blueprint $table): void {
            $table->string('actor_id')->primary();
            $table->string('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_accesses');
    }
};
