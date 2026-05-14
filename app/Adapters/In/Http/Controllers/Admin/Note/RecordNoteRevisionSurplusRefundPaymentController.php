<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Note;

use App\Adapters\In\Http\Requests\Note\RecordNoteRevisionSurplusRefundPaymentRequest;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentCommand;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentHandler;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class RecordNoteRevisionSurplusRefundPaymentController extends Controller
{
    public function __invoke(
        string $dispositionId,
        RecordNoteRevisionSurplusRefundPaymentRequest $request,
        RecordNoteRevisionSurplusRefundPaymentHandler $handler,
    ): RedirectResponse {
        $data = $request->validated();
        $user = $request->user();

        $actorId = $user !== null ? (string) $user->getAuthIdentifier() : '';

        $result = $handler->handle(new RecordNoteRevisionSurplusRefundPaymentCommand(
            noteRevisionSurplusDispositionId: trim($dispositionId),
            amountRupiah: (int) $data['amount_rupiah'],
            effectiveDate: new DateTimeImmutable((string) $data['effective_date']),
            reason: (string) $data['reason'],
            actorId: $actorId,
            actorRole: 'admin',
            idempotencyKey: (string) $data['idempotency_key'],
            occurredAt: null,
            sourceChannel: 'web_admin',
            requestId: $request->headers->get('X-Request-Id'),
            correlationId: $request->headers->get('X-Correlation-Id'),
        ));

        if ($result->isFailure()) {
            return back()
                ->withErrors([
                    'refund_paid' => $result->message() ?? 'Refund paid gagal dicatat.',
                ])
                ->withInput();
        }

        $noteRootId = (string) ($result->data()['note_root_id'] ?? '');

        return redirect()
            ->route('admin.notes.show', ['noteId' => $noteRootId])
            ->with('success', 'Refund paid berhasil dicatat.');
    }
}
