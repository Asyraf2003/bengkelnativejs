<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_revision_surplus_refund_payments', function (Blueprint $table): void {
            $table->string('id')->primary();

            $table->string('note_revision_surplus_disposition_id');
            $table->string('note_revision_settlement_id');
            $table->string('note_root_id');
            $table->string('note_revision_id');

            $table->bigInteger('amount_rupiah');
            $table->date('effective_date');
            $table->dateTime('occurred_at');

            $table->string('status', 32);
            $table->string('idempotency_key', 128);
            $table->string('audit_event_id');

            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->unique(
                'audit_event_id',
                'nr_surplus_refund_payments_audit_event_unique'
            );
            $table->unique(
                ['note_revision_surplus_disposition_id', 'idempotency_key'],
                'nr_surplus_refund_payments_disposition_idem_unique'
            );

            $table->index(
                ['note_revision_surplus_disposition_id', 'status'],
                'nr_surplus_refund_payments_disposition_status_idx'
            );
            $table->index(
                ['note_root_id', 'status'],
                'nr_surplus_refund_payments_root_status_idx'
            );
            $table->index(
                ['note_root_id', 'occurred_at'],
                'nr_surplus_refund_payments_root_occurred_idx'
            );
            $table->index(
                ['note_root_id', 'effective_date'],
                'nr_surplus_refund_payments_root_effective_idx'
            );
            $table->index(
                ['effective_date', 'status'],
                'nr_surplus_refund_payments_effective_status_idx'
            );
            $table->index(
                ['status', 'effective_date'],
                'nr_surplus_refund_payments_status_effective_idx'
            );
            $table->index(
                'note_revision_settlement_id',
                'nr_surplus_refund_payments_settlement_idx'
            );
            $table->index(
                'note_revision_id',
                'nr_surplus_refund_payments_revision_idx'
            );

            $table->foreign(
                'note_revision_surplus_disposition_id',
                'fk_nr_surplus_refund_payments_disposition'
            )
                ->references('id')
                ->on('note_revision_surplus_dispositions')
                ->restrictOnDelete();

            $table->foreign(
                'note_revision_settlement_id',
                'fk_nr_surplus_refund_payments_settlement'
            )
                ->references('id')
                ->on('note_revision_settlements')
                ->restrictOnDelete();

            $table->foreign(
                'note_root_id',
                'fk_nr_surplus_refund_payments_note_root'
            )
                ->references('id')
                ->on('notes')
                ->restrictOnDelete();

            $table->foreign(
                'note_revision_id',
                'fk_nr_surplus_refund_payments_revision'
            )
                ->references('id')
                ->on('note_revisions')
                ->restrictOnDelete();

            $table->foreign(
                'audit_event_id',
                'fk_nr_surplus_refund_payments_audit_event'
            )
                ->references('id')
                ->on('audit_events')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('note_revision_surplus_refund_payments', function (Blueprint $table): void {
            $table->dropForeign('fk_nr_surplus_refund_payments_audit_event');
            $table->dropForeign('fk_nr_surplus_refund_payments_revision');
            $table->dropForeign('fk_nr_surplus_refund_payments_note_root');
            $table->dropForeign('fk_nr_surplus_refund_payments_settlement');
            $table->dropForeign('fk_nr_surplus_refund_payments_disposition');
        });

        Schema::dropIfExists('note_revision_surplus_refund_payments');
    }
};
