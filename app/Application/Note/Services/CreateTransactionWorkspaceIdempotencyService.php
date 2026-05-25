<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\IdempotencyRecordPort;

final class CreateTransactionWorkspaceIdempotencyService
{
    private const OPERATION = 'create_transaction_workspace';

    public function __construct(
        private readonly IdempotencyRecordPort $records,
        private readonly CreateTransactionWorkspaceIdempotencyScopeResolver $scopes,
    ) {
    }

    public function replay(array $payload): ?Result
    {
        $scope = $this->scopes->resolve($payload);

        if ($scope === null) {
            return null;
        }

        $record = $this->records->find($scope['actor_id'], self::OPERATION, $scope['key']);

        if ($record === null) {
            return null;
        }

        if ($record['request_hash'] !== $scope['hash']) {
            return Result::failure('Idempotency key sudah dipakai untuk payload berbeda.', [
                'idempotency_key' => ['IDEMPOTENCY_KEY_PAYLOAD_MISMATCH'],
            ]);
        }

        if ($record['status'] !== 'succeeded') {
            return Result::failure('Workspace nota sedang diproses. Coba ulang beberapa saat lagi.', [
                'idempotency_key' => ['IDEMPOTENCY_KEY_PROCESSING'],
            ]);
        }

        return Result::success(
            $record['result_payload']['data'] ?? null,
            'Workspace nota sudah diproses sebelumnya.'
        );
    }

    public function start(array $payload): void
    {
        $scope = $this->scopes->resolve($payload);

        if ($scope === null) {
            return;
        }

        $this->records->createProcessing(
            $scope['actor_id'],
            self::OPERATION,
            $scope['key'],
            $scope['hash']
        );
    }

    public function succeed(array $payload, Result $result): void
    {
        $scope = $this->scopes->resolve($payload);

        if ($scope === null) {
            return;
        }

        $data = is_array($result->data()) ? $result->data() : [];
        $noteId = $data['note']['id'] ?? null;

        $this->records->markSucceeded(
            $scope['actor_id'],
            self::OPERATION,
            $scope['key'],
            ['data' => $result->data(), 'message' => $result->message()],
            is_string($noteId) ? $noteId : null
        );
    }
}
