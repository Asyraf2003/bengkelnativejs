<?php

declare(strict_types=1);

namespace App\Adapters\Out\IdentityAccess;

use App\Core\IdentityAccess\Actor\ActorAccess;
use App\Core\IdentityAccess\Role\Role;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseActorAccessReaderAdapter implements ActorAccessReaderPort
{
    public function findByActorId(string $actorId): ?ActorAccess
    {
        $row = DB::table('actor_accesses')
            ->select(['actor_id', 'role'])
            ->where('actor_id', $actorId)
            ->first();

        if ($row === null) {
            return null;
        }

        return new ActorAccess(
            (string) $row->actor_id,
            Role::fromString((string) $row->role),
        );
    }
}
