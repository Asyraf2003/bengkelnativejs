<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CreateNoteRevisionHandler
{
    public function __construct(
        private readonly CreateNoteRevisionWorkflow $workflow,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function handle(
        string $noteRootId,
        array $payload,
        ?string $actorId = null,
        bool $enforceWorkspaceEditability = true,
    ): CreateNoteRevisionResult {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $result = $this->workflow->execute(
                $noteRootId,
                $payload,
                $actorId,
                $enforceWorkspaceEditability,
            );

            $this->transactions->commit();

            return $result;
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return CreateNoteRevisionResult::failure($e->getMessage());
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }
}
