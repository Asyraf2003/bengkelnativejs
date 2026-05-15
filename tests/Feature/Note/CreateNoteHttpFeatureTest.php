<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateNoteHttpFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_cashier_can_create_note_with_service_row_via_transaction_entry_route(): void
    {
        $this->loginAsKasir();
        $user = User::query()->create([
            'name' => 'Kasir Aktif',
            'email' => 'cashier@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->post('/notes/create', [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-03-14',
            'rows' => [
                [
                    'line_type' => 'service',
                    'service_name' => 'Servis Ringan',
                    'service_price_rupiah' => 150000,
                    'service_notes' => 'Ganti oli dan cek rem',
                ],
            ],
        ]);

        $note = DB::table('notes')
            ->where('customer_name', 'Budi Santoso')
            ->where('transaction_date', '2026-03-14')
            ->first();

        $this->assertNotNull($note);

        $response->assertRedirect(
            route('cashier.notes.show', ['noteId' => (string) $note->id])
        );

        $this->assertDatabaseHas('notes', [
            'id' => (string) $note->id,
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => (string) $note->id,
            'line_no' => 1,
            'transaction_type' => 'service_only',
            'subtotal_rupiah' => 150000,
        ]);
    }
}
