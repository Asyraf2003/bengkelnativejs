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

final class CashierDetailRenderedBillingRowsPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_rendered_detail_billing_row_ids_can_settle_note_with_transfer(): void
    {
        $user = $this->seedKasir();
        $this->seedMixedNote();

        $html = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->assertOk()
            ->getContent();

        preg_match_all('/data-billing-row-id="([^"]+)"/', $html, $matches);

        $selectedRowIds = array_values(array_unique($matches[1]));

        $this->assertNotSame([], $selectedRowIds, 'Detail page must render payment billing row IDs.');

        $response = $this->actingAs($user)->from(route('cashier.notes.show', ['noteId' => 'note-1']))
            ->post(route('cashier.notes.payments.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => $selectedRowIds,
                'payment_method' => 'tf',
                'paid_at' => date('Y-m-d'),
            ]);

        $response->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'ssl-1',
            'allocated_amount_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'allocated_amount_rupiah' => 30000,
        ]);
    }


    public function test_canceled_rows_are_not_rendered_as_payable_billing_rows(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-canceled-render-1', 'Budi Render Canceled', $today, 100000, 'open');

        $this->seedWorkItemBase(
            'wi-render-active-1',
            'note-canceled-render-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            50000
        );
        $this->seedServiceDetailBase(
            'wi-render-active-1',
            'Servis Render Aktif',
            50000,
            ServiceDetail::PART_SOURCE_NONE
        );

        $this->seedWorkItemBase(
            'wi-render-canceled-1',
            'note-canceled-render-1',
            2,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_CANCELED,
            50000
        );
        $this->seedServiceDetailBase(
            'wi-render-canceled-1',
            'Servis Render Batal',
            50000,
            ServiceDetail::PART_SOURCE_NONE
        );

        $html = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-canceled-render-1']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-billing-row-id="wi-render-active-1::service_fee::wi-render-active-1"',
            $html
        );
        $this->assertStringNotContainsString(
            'data-billing-row-id="wi-render-canceled-1::service_fee::wi-render-canceled-1"',
            $html
        );
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Rendered Billing Rows',
            'email' => 'kasir-rendered-billing@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedMixedNote(): void
    {
        $today = date('Y-m-d');

        DB::table('products')->insert([
            'id' => 'prod-1',
            'kode_barang' => 'PRD-RENDER-001',
            'nama_barang' => 'Sparepart Rendered',
            'nama_barang_normalized' => 'sparepart rendered',
            'merek' => 'TEST',
            'merek_normalized' => 'test',
            'ukuran' => 1,
            'harga_jual' => 20000,
            'reorder_point_qty' => 1,
            'critical_threshold_qty' => 1,
        ]);

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis Campuran', 30000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('work_item_store_stock_lines')->insert([
            'id' => 'ssl-1',
            'work_item_id' => 'wi-1',
            'product_id' => 'prod-1',
            'qty' => 1,
            'line_total_rupiah' => 20000,
        ]);
    }
}
