<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_no')->unique();
            $table->string('supplier_name');

            $table->date('delivered_at');
            $table->date('due_at');

            $table->boolean('is_paid')->default(false)->index();
            $table->date('paid_at')->nullable();

            $table->unsignedBigInteger('grand_total')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['due_at', 'is_paid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
