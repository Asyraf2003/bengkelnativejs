<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_service_details', function (Blueprint $table): void {
            $table->string('work_item_id')->primary();
            $table->string('service_name');
            $table->integer('service_price_rupiah');
            $table->string('part_source');

            $table->index('part_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_service_details');
    }
};
