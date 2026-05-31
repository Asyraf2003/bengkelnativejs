<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoice_list_projection', function (Blueprint $table): void {
            $table->index(
                ['voided_at', 'nomor_faktur_normalized', 'shipment_date', 'supplier_invoice_id'],
                'silp_voided_invoice_norm_shipdesc_invoiceasc_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_list_projection', function (Blueprint $table): void {
            $table->dropIndex('silp_voided_invoice_norm_shipdesc_invoiceasc_idx');
        });
    }
};
