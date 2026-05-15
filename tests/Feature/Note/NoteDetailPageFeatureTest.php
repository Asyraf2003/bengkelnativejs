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

final class NoteDetailPageFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_can_see_row_settlement_labels_and_outstanding_values(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Detail',
            'email' => 'cashier-note-detail@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $today = now()->toDateString();

        $this->seedNotePaymentProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 5000);
        $this->seedNotePaymentProduct('product-2', 'KB-002', 'Kampas Rem', 'Federal', 90, 3000);

        $this->seedNoteBase('note-1', 'Budi', $today, 26000, 'open');

        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 5000);
        $this->seedWorkItemBase('wi-2', 'note-1', 2, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 8000);
        $this->seedWorkItemBase('wi-3', 'note-1', 3, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 13000);

        $this->seedServiceDetailBase('wi-2', 'Servis A', 5000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceDetailBase('wi-3', 'Servis B', 13000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedStoreStockLineBase('sto-1', 'wi-1', 'product-1', 1, 5000);
        $this->seedStoreStockLineBase('sto-2', 'wi-2', 'product-2', 1, 3000);

        $this->seedCustomerPaymentBase('pay-1', 8000, $today);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 5000,
                'allocated_amount_rupiah' => 5000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-2',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'sto-2',
                'component_amount_rupiah_snapshot' => 3000,
                'allocated_amount_rupiah' => 3000,
                'allocation_priority' => 2,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('cashier.notes.show', ['noteId' => 'note-1']));

        $response->assertOk();
        $response->assertSee('Sudah Dibayar');
        $response->assertSee('Sisa Tagihan');
        $response->assertSee('8.000', false);
        $response->assertSee('18.000', false);
        $response->assertSee('3.000', false);
        $response->assertSee('13.000', false);
    }
}
