<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('note_id');
            $table->integer('line_no');
            $table->string('transaction_type');
            $table->string('status');
            $table->integer('subtotal_rupiah');

            $table->index('note_id');
            $table->index(['note_id', 'line_no']);
            $table->index('transaction_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
