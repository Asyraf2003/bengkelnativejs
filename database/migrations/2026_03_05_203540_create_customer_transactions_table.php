<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_order_id')
                ->constrained('customer_orders')
                ->cascadeOnDelete();

            $table->string('customer_name');

            $table->enum('status', ['draft', 'paid', 'canceled', 'refunded'])->index();

            $table->date('transacted_at'); // tanggal transaksi anak dibuat
            $table->date('paid_at')->nullable(); // tanggal uang masuk
            $table->date('refunded_at')->nullable(); // tanggal uang keluar balik

            $table->unsignedBigInteger('refund_amount')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['customer_order_id', 'transacted_at']);
            $table->index(['status', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
