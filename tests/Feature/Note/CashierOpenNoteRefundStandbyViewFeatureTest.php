<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierOpenNoteRefundStandbyViewFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_open_unpaid_note_shows_refund_or_cancel_line_standby_action(): void
    {
        $user = $this->seedKasir();

        $today = date('Y-m-d');
        $this->seedNoteBase('note-1', 'Budi', $today, 30000, 'open');
        $this->seedWorkItemBase(
            'wi-1',
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            30000
        );
        $this->seedServiceDetailBase('wi-1', 'Servis Ringan', 30000, 'none');

        $response = $this->actingAs($user)
            ->get(route('cashier.notes.show', ['noteId' => 'note-1']));

        $response->assertOk();
        $response->assertSee('Refund / Batalkan Line', false);
        $response->assertSee('data-refund-row="1"', false);
        $response->assertSee('data-row-id="wi-1"', false);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Standby',
            'email' => 'kasir-refund-standby@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }
}
