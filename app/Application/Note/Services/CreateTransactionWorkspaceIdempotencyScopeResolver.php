<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class CreateTransactionWorkspaceIdempotencyScopeResolver
{
    /**
     * @return array{actor_id:string,key:string,hash:string}|null
     */
    public function resolve(array $payload): ?array
    {
        $key = trim((string) ($payload['idempotency_key'] ?? ''));

        if ($key === '') {
            return null;
        }

        $actorId = trim((string) ($payload['_actor_id'] ?? ''));

        return [
            'actor_id' => $actorId !== '' ? $actorId : 'anonymous',
            'key' => $key,
            'hash' => $this->hash($payload),
        ];
    }

    private function hash(array $payload): string
    {
        unset($payload['_actor_id'], $payload['idempotency_key']);

        $this->sortRecursive($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function sortRecursive(array &$value): void
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sortRecursive($item);
            }
        }

        ksort($value);
    }
}
