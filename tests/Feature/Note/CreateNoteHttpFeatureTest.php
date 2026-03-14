<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateNoteHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_cashier_can_create_note_via_transaction_entry_route(): void
    {
        $user = User::query()->create([
            'name' => 'Kasir Aktif',
            'email' => 'cashier@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->postJson('/notes/create', [
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('notes', [
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 0,
        ]);
    }
}
