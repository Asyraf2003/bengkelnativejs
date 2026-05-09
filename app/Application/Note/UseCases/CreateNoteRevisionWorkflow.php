<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\ApplyNoteRevisionAsActiveReplacement;
use App\Application\Note\Services\EditableWorkspaceNoteGuard;
use App\Application\Note\Services\NoteCurrentRevisionResolver;
use App\Application\Note\Services\NoteRevisionBootstrapFactory;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;

final class CreateNoteRevisionWorkflow
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteCurrentRevisionResolver $current,
        private readonly NoteRevisionBootstrapFactory $factory,
        private readonly CreateNoteRevisionPayloadNoteBuilder $payloadNotes,
        private readonly CreateNoteRevisionCommitter $committer,
        private readonly ApplyNoteRevisionAsActiveReplacement $applier,
        private readonly EditableWorkspaceNoteGuard $guard,
        private readonly ClockPort $clock,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function execute(
        string $noteRootId,
        array $payload,
        ?string $actorId,
        bool $enforceWorkspaceEditability = true,
    ): CreateNoteRevisionResult {
        $root = $this->notes->getByIdForUpdate(trim($noteRootId));

        if ($root === null) {
            return CreateNoteRevisionResult::failure('Root note tidak ditemukan.');
        }

        if ($enforceWorkspaceEditability) {
            $this->guard->assertEditable($root->id());
        }

        $current = $this->current->resolveOrFail($root->id());
        $number = $this->current->nextRevisionNumber($root->id());
        $reason = (string) ($payload['reason'] ?? '');
        $replacement = $this->payloadNotes->build(
            $root->id(),
            $payload,
            $current,
            $root->workItems(),
        );

        $this->applier->apply($root, $replacement, $payload['items'] ?? []);

        $revision = $this->factory->createNextRevision(
            sprintf('%s-r%03d', $root->id(), $number),
            $current->id(),
            $number,
            $root,
            $actorId,
            $this->clock->now(),
            $reason,
        );

        return $this->committer->commit(
            $root->id(),
            $current->id(),
            $actorId,
            $reason,
            $revision,
        );
    }
}
