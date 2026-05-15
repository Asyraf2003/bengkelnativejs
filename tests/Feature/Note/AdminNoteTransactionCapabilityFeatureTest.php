<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminNoteTransactionCapabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_transaction_capability_can_still_read_admin_note_pages(): void
    {
        $admin = $this->adminWithoutTransactionCapability();

        $this->actingAs($admin)
            ->get(route('admin.notes.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('admin.notes.table'))
            ->assertOk();
    }

    public function test_admin_without_transaction_capability_is_rejected_from_admin_note_mutation_routes(): void
    {
        $admin = $this->adminWithoutTransactionCapability();

        foreach ($this->adminMutationRequests() as $request) {
            $response = $this->actingAs($admin)
                ->{$request['method']}($request['route'], $request['payload']);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'data' => null,
                'message' => 'Admin belum diizinkan input transaksi.',
                'errors' => [
                    'capability' => ['ADMIN_TRANSACTION_CAPABILITY_DISABLED'],
                ],
            ]);
        }
    }

    /**
     * @return list<array{name:string,method:string,route:string,payload:array<string,mixed>}>
     */
    private function adminMutationRequests(): array
    {
        return [
            [
                'name' => 'payment',
                'method' => 'postJson',
                'route' => route('admin.notes.payments.store', ['noteId' => 'note-020']),
                'payload' => [
                    'payment_method' => 'cash',
                    'paid_at' => date('Y-m-d'),
                    'amount_paid' => 10000,
                    'amount_received' => 10000,
                ],
            ],
            [
                'name' => 'refund',
                'method' => 'postJson',
                'route' => route('admin.notes.refunds.store', ['noteId' => 'note-020']),
                'payload' => [
                    'refund_date' => date('Y-m-d'),
                    'refund_amount_rupiah' => 10000,
                    'reason' => 'Capability guard regression test.',
                ],
            ],
            [
                'name' => 'rows',
                'method' => 'postJson',
                'route' => route('admin.notes.rows.store', ['noteId' => 'note-020']),
                'payload' => [
                    'rows' => [],
                ],
            ],
            [
                'name' => 'workspace',
                'method' => 'patchJson',
                'route' => route('admin.notes.workspace.update', ['noteId' => 'note-020']),
                'payload' => [
                    'customer_name' => 'Budi',
                    'transaction_date' => date('Y-m-d'),
                    'items' => [],
                ],
            ],
        ];
    }

    private function adminWithoutTransactionCapability(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Tanpa Capability Note',
            'email' => 'admin-note-no-capability@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
