<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class NotePrototypeRedirectFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_prototype_route_is_not_available_anymore(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir',
            'email' => 'cashier@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->get('/cashier/notes/prototype/note-123');

        $response->assertNotFound();
    }
}
