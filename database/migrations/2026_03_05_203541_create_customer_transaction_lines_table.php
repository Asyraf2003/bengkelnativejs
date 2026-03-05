<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_transaction_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_transaction_id')
                ->constrained('customer_transactions')
                ->cascadeOnDelete();

            $table->enum('kind', [
                'product_sale',
                'service_fee',
                'service_product',
                'outside_cost',
            ])->index();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->restrictOnDelete();

            $table->unsignedInteger('qty')->nullable(); // hanya utk line stok
            $table->unsignedBigInteger('amount'); // rupiah int (revenue atau expense)

            // diisi saat Paid (nanti step 2)
            $table->unsignedBigInteger('cogs_amount')->nullable();

            // untuk konsistensi refund_in: unit cost saat sale (diisi saat Paid nanti)
            $table->unsignedBigInteger('sale_unit_cost')->nullable();

            // untuk refund partial (qty yang sudah direfund per line stok)
            $table->unsignedInteger('refunded_qty')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['customer_transaction_id', 'product_id'], 'ctx_lines_tx_prod_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transaction_lines');
    }
};
