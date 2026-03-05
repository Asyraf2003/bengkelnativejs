<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('loaned_at')->index(); // uang keluar
            $table->unsignedBigInteger('amount');

            // opsi 2: nullable di DB
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'loaned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
