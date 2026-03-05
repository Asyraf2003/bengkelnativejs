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

            $table->string('customer_name');

            $table->enum('status', ['draft', 'paid', 'canceled', 'refunded'])->index();

            $table->date('transacted_at'); // tanggal dibuat
            $table->date('paid_at')->nullable(); // tanggal pelunasan
            $table->date('refunded_at')->nullable(); // tanggal refund (1x per transaksi)

            // untuk mendukung refund partial: total uang refund (cash-out)
            $table->unsignedBigInteger('refund_amount')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['status', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
