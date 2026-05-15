<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CashierNoteHistoryPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_cashier_can_access_note_history_shell_page(): void
    {
        $this->loginAsKasir();
        $user = $this->cashierUser();

        $response = $this->actingAs($user)->get(route('cashier.notes.index'));

        $response->assertOk();
        $response->assertSee('Daftar Nota Kasir');
        $response->assertSee('cashier-note-search-input', false);
        $response->assertSee('cashier-note-table-body', false);
        $response->assertSee('cashier-note-index.js');
        $response->assertSee(json_encode(route('cashier.notes.table')), false);
    }

    private function cashierUser(): User
    {
        $user = User::query()->create([
            'name' => 'Kasir Riwayat',
            'email' => 'cashier-note-history@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
