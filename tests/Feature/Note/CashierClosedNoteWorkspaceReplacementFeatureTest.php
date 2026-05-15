<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierClosedNoteWorkspaceReplacementFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_can_open_workspace_edit_for_fully_settled_note_replacement(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Lama', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedCustomerPaymentBase('pay-1', 50000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'pay-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 20,
        ]);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.workspace.edit', ['noteId' => 'note-1']));

        $response->assertOk();
        $response->assertSee('Edit Nota');
        $response->assertSee('Servis Lama');
        $response->assertSee('Budi');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Closed Replacement',
            'email' => 'kasir-closed-replacement@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
