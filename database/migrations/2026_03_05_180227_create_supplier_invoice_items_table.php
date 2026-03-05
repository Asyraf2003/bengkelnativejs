<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_invoice_id')
                ->constrained('supplier_invoices')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->unsignedInteger('qty');

            // sumber kebenaran biaya item
            $table->unsignedBigInteger('total_cost');

            // untuk referensi saja (display); avg_cost dihitung pakai total_cost
            $table->unsignedBigInteger('unit_cost');

            $table->timestamps();

            $table->index(['supplier_invoice_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_items');
    }
};
