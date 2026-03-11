<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('product_id');
            $table->string('movement_type');
            $table->string('source_type');
            $table->string('source_id');
            $table->date('tanggal_mutasi');
            $table->integer('qty_delta');
            $table->integer('unit_cost_rupiah');
            $table->integer('total_cost_rupiah');

            $table->index('product_id');
            $table->index(['source_type', 'source_id']);
            $table->index('tanggal_mutasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
