<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoice_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_invoice_id')
                ->constrained('supplier_invoices')
                ->cascadeOnDelete();

            // relative path di disk local, contoh: private/invoices/1/<uuid>.pdf
            $table->string('path_private');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('uploaded_at');

            $table->timestamps();

            $table->index(['supplier_invoice_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_media');
    }
};
