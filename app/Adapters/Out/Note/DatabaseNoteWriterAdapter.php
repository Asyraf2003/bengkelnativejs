<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Core\Note\Note\Note;
use App\Ports\Out\Note\NoteWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteWriterAdapter implements NoteWriterPort
{
    public function create(Note $note): void
    {
        DB::table('notes')->insert([
            'id' => $note->id(),
            'customer_name' => $note->customerName(),
            'transaction_date' => $note->transactionDate()->format('Y-m-d'),
            'total_rupiah' => $note->totalRupiah()->amount(),
        ]);
    }

    public function updateTotal(Note $note): void
    {
        DB::table('notes')
            ->where('id', $note->id())
            ->update([
                'total_rupiah' => $note->totalRupiah()->amount(),
            ]);
    }
}
