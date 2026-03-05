<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventory', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->primary();

            $table->unsignedInteger('on_hand_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);

            // moving average cost dalam rupiah (int)
            $table->unsignedBigInteger('avg_cost')->default(0);

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventory');
    }
};
