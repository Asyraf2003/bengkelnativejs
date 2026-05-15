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

final class CashierNoteDetailServiceProductNameDisplayFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_service_with_store_stock_detail_shows_product_name_not_product_id(): void
    {
        $user = $this->seedKasir();

        DB::table('products')->insert([
            'id' => 'product-oli-1',
            'kode_barang' => 'OLI-001',
            'nama_barang' => 'Oli Federal Matic',
            'nama_barang_normalized' => 'oli federal matic',
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 800,
            'harga_jual' => 50000,
        ]);

        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 130000, 'open');
        $this->seedWorkItemBase(
            'wi-1',
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            130000
        );
        $this->seedServiceDetailBase('wi-1', 'Ganti Oli', 30000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedStoreStockLineBase('line-1', 'wi-1', 'product-oli-1', 2, 100000);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']));

        $response->assertOk();
        $response->assertSee('Ganti Oli');
        $response->assertSee('Oli Federal Matic');
        $response->assertDontSee('product-oli-1 x2');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Service Product Name Display',
            'email' => 'kasir-service-product-name-display@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
