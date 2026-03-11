<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_receipt_lines', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('supplier_receipt_id');
            $table->string('supplier_invoice_line_id');
            $table->integer('qty_diterima');

            $table->index('supplier_receipt_id');
            $table->index('supplier_invoice_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_receipt_lines');
    }
};
