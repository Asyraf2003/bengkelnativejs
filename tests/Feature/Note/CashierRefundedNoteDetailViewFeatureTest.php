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

final class CashierRefundedNoteDetailViewFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_refunded_note_detail_hides_payment_actions_and_workspace_edit(): void
    {
        $user = $this->seedKasir();
        $this->seedRefundedPaidServiceOnlyNote();

        $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()->assertSee('Workspace Nota Kasir')->assertSee('Detail Nota')
            ->assertSee('Header Nota')->assertSee('List Line Nota')
            ->assertSee('List Line Nota')
            ->assertDontSee('Lunasi')->assertDontSee('Status & Aksi Nota')
            ->assertDontSee('Edit Nota')
            ->assertDontSee(route('cashier.notes.workspace.edit', ['noteId' => 'note-1']), false);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();
        $user = User::query()->create(['name' => 'Kasir Refunded View', 'email' => 'cashier-refunded-view@example.test', 'password' => 'password']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'kasir']);
        return $user;
    }

    private function seedRefundedPaidServiceOnlyNote(): void
    {
        $today = date('Y-m-d');
        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'refunded');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('payment-1', 50000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);
        DB::table('payment_component_allocations')->insert(['id' => 'pca-1', 'customer_payment_id' => 'payment-1', 'note_id' => 'note-1', 'work_item_id' => 'wi-1', 'component_type' => 'service_fee', 'component_ref_id' => 'wi-1', 'component_amount_rupiah_snapshot' => 50000, 'allocated_amount_rupiah' => 50000, 'allocation_priority' => 1]);
        DB::table('customer_refunds')->insert(['id' => 'refund-1', 'customer_payment_id' => 'payment-1', 'note_id' => 'note-1', 'amount_rupiah' => 50000, 'refunded_at' => $today, 'reason' => 'Refund penuh servis']);
    }
}
