<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceTemplateContractFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_create_page_embeds_explicit_service_part_source_values(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Template',
            'email' => 'template-contract@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->get(route('cashier.notes.workspace.create'));

        $response->assertOk();
        $response->assertSee('name="items[__INDEX__][part_source]" value="none"', false);
        $response->assertSee('name="items[__INDEX__][part_source]" value="store_stock"', false);
        $response->assertSee('name="items[__INDEX__][part_source]" value="external_purchase"', false);
    }
}
