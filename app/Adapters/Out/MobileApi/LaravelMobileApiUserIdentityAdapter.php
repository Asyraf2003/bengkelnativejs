<?php

declare(strict_types=1);

namespace App\Adapters\Out\MobileApi;

use App\Application\MobileApi\Auth\DTO\MobileApiAuthenticatedUser;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Ports\Out\MobileApi\MobileApiCredentialVerifierPort;
use App\Ports\Out\MobileApi\MobileApiUserReaderPort;
use Illuminate\Support\Facades\Hash;

final class LaravelMobileApiUserIdentityAdapter implements MobileApiCredentialVerifierPort, MobileApiUserReaderPort
{
    public function verify(string $email, string $password): ?MobileApiAuthenticatedUser
    {
        $user = User::query()
            ->where('email', trim($email))
            ->first();

        if (!$user instanceof User) {
            return null;
        }

        if (!Hash::check($password, (string) $user->getAttribute('password'))) {
            return null;
        }

        return $this->mapUser($user);
    }

    public function findById(string $userId): ?MobileApiAuthenticatedUser
    {
        $user = User::query()->find($userId);

        if (!$user instanceof User) {
            return null;
        }

        return $this->mapUser($user);
    }

    private function mapUser(User $user): MobileApiAuthenticatedUser
    {
        return new MobileApiAuthenticatedUser(
            id: (string) $user->getAuthIdentifier(),
            name: (string) $user->getAttribute('name'),
            email: (string) $user->getAttribute('email'),
        );
    }
}
