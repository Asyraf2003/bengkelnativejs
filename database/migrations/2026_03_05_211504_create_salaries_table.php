<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('paid_at')->index();
            $table->unsignedBigInteger('amount');

            // opsi 2: nullable di DB
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
