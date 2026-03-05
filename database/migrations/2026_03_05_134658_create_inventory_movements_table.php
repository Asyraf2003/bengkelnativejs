<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->enum('type', [
                'invoice_in',
                'sale_out',
                'refund_in',
                'adjust_in',
                'adjust_out',
                'reserve',
                'release',
            ])->index();

            // qty signed: + masuk, - keluar (sesuai blueprint)
            $table->integer('qty');

            // unit_cost dipakai utk movement yang mempengaruhi valuation
            $table->unsignedBigInteger('unit_cost')->nullable();

            $table->string('ref_type', 50)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['ref_type', 'ref_id']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
