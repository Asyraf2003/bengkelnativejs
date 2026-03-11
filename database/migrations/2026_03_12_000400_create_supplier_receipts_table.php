<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_receipts', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('supplier_invoice_id');
            $table->date('tanggal_terima');

            $table->index('supplier_invoice_id');
            $table->index('tanggal_terima');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_receipts');
    }
};
