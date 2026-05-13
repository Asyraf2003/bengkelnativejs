<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;

interface NoteRevisionSurplusRefundPaymentWriterPort
{
    public function create(NoteRevisionSurplusRefundPayment $payment): void;
}
