<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_outbox', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('audit_event_id');
            $table->string('bounded_context');
            $table->string('aggregate_type');
            $table->string('aggregate_id');
            $table->string('event_name');
            $table->string('actor_id')->nullable();
            $table->string('actor_role')->nullable();
            $table->text('reason')->nullable();
            $table->string('source_channel')->nullable();
            $table->string('request_id')->nullable();
            $table->string('correlation_id')->nullable();
            $table->dateTime('occurred_at');
            $table->json('metadata_json')->nullable();
            $table->json('snapshots_json')->nullable();
            $table->string('status');
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->dateTime('available_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique('audit_event_id', 'audit_outbox_audit_event_id_unique');
            $table->index(['status', 'available_at'], 'audit_outbox_status_available_idx');
            $table->index(['bounded_context', 'occurred_at'], 'audit_outbox_context_occurred_idx');
            $table->index(['aggregate_type', 'aggregate_id', 'occurred_at'], 'audit_outbox_aggregate_lookup_idx');
            $table->index(['event_name', 'occurred_at'], 'audit_outbox_event_occurred_idx');
            $table->index('correlation_id', 'audit_outbox_correlation_id_idx');
            $table->index('request_id', 'audit_outbox_request_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_outbox');
    }
};
