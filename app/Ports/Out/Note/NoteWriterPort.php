<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Core\Note\Note\Note;

interface NoteWriterPort
{
    public function create(Note $note): void;

    public function updateTotal(Note $note): void;
}
