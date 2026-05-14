<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\Note\Note;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class NoteOperationalStatePersistenceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_and_reads_operational_state_metadata(): void
    {
        $note = Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-03'),
            Money::fromInt(10000),
            [],
            Note::STATE_CLOSED,
            new DateTimeImmutable('2026-04-03 10:00:00'),
            'admin-1',
            new DateTimeImmutable('2026-04-03 11:00:00'),
            'admin-2',
        );

        try {
            Carbon::setTestNow(Carbon::parse('2026-04-03 09:00:00'));

            app(NoteWriterPort::class)->create($note);
        } finally {
            Carbon::setTestNow();
        }

        $row = DB::table('notes')->where('id', 'note-1')->first();
        $loaded = app(NoteReaderPort::class)->getById('note-1');

        $this->assertNotNull($row);
        $this->assertSame('2026-04-03 09:00:00', (string) $row->created_at);
        $this->assertSame('2026-04-03 09:00:00', (string) $row->updated_at);

        $this->assertNotNull($loaded);
        $this->assertSame(Note::STATE_CLOSED, $loaded->noteState());
        $this->assertSame('2026-04-03 10:00:00', $loaded->closedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-1', $loaded->closedByActorId());
        $this->assertSame('2026-04-03 11:00:00', $loaded->reopenedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-2', $loaded->reopenedByActorId());
    }

    public function test_it_updates_operational_state_metadata(): void
    {
        $open = Note::create('note-2', 'Sari', null, new DateTimeImmutable('2026-04-03'));
        try {
            Carbon::setTestNow(Carbon::parse('2026-04-03 09:00:00'));

            app(NoteWriterPort::class)->create($open);
        } finally {
            Carbon::setTestNow();
        }

        $closed = Note::rehydrate(
            'note-2',
            'Sari',
            null,
            new DateTimeImmutable('2026-04-03'),
            Money::zero(),
            [],
            Note::STATE_CLOSED,
            new DateTimeImmutable('2026-04-03 12:00:00'),
            'admin-3',
            null,
            null,
        );

        try {
            Carbon::setTestNow(Carbon::parse('2026-04-03 13:00:00'));

            app(NoteWriterPort::class)->updateOperationalState($closed);
        } finally {
            Carbon::setTestNow();
        }

        $row = DB::table('notes')->where('id', 'note-2')->first();
        $loaded = app(NoteReaderPort::class)->getById('note-2');

        $this->assertNotNull($row);
        $this->assertSame('2026-04-03 09:00:00', (string) $row->created_at);
        $this->assertSame('2026-04-03 13:00:00', (string) $row->updated_at);

        $this->assertNotNull($loaded);
        $this->assertSame(Note::STATE_CLOSED, $loaded->noteState());
        $this->assertSame('2026-04-03 12:00:00', $loaded->closedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('admin-3', $loaded->closedByActorId());
    }
}
