<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table): void {
            if (! Schema::hasColumn('notes', 'created_at')) {
                $table->dateTime('created_at')->nullable();
            }

            if (! Schema::hasColumn('notes', 'updated_at')) {
                $table->dateTime('updated_at')->nullable();
            }
        });

        $now = now()->format('Y-m-d H:i:s');

        DB::table('notes')
            ->whereNull('created_at')
            ->update([
                'created_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table): void {
            if (Schema::hasColumn('notes', 'updated_at')) {
                $table->dropColumn('updated_at');
            }

            if (Schema::hasColumn('notes', 'created_at')) {
                $table->dropColumn('created_at');
            }
        });
    }
};
