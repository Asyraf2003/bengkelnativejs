<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loan_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_loan_id')
                ->constrained('employee_loans')
                ->cascadeOnDelete();

            $table->date('paid_at')->index(); // uang masuk
            $table->unsignedBigInteger('amount');

            // opsi 2: nullable di DB
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['employee_loan_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_payments');
    }
};
