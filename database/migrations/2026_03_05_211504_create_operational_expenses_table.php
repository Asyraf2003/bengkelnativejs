<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->date('spent_at')->index();
            $table->unsignedBigInteger('amount'); // rupiah int

            // opsi 2: nullable di DB, tapi nanti wajib via validasi
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['spent_at', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_expenses');
    }
};
