<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventory', function (Blueprint $table): void {
            $table->string('product_id')->primary();
            $table->integer('qty_on_hand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventory');
    }
};
