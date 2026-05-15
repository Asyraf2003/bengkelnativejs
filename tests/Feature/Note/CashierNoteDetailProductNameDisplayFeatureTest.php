<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteDetailProductNameDisplayFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_detail_and_versioning_show_product_name_not_product_id(): void
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

        $this->seedNoteBase('note-1', 'Budi', $today, 100000, 'open');
        $this->seedWorkItemBase(
            'wi-1',
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_OPEN,
            100000
        );
        $this->seedStoreStockLineBase('line-1', 'wi-1', 'product-oli-1', 2, 100000);

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']));

        $response->assertOk();
        $response->assertSee('Oli Federal Matic');
        $response->assertDontSee('Produk product-oli-1');
        $response->assertDontSee('product-oli-1 x2');
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Product Name Display',
            'email' => 'kasir-product-name-display@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
