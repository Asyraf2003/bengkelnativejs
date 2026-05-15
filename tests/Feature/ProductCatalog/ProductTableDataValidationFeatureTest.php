<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductTableDataValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_validation_error_when_ukuran_range_is_invalid(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.products.table', ['ukuran_min' => 100, 'ukuran_max' => 90]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ukuran_min']);
    }

    public function test_admin_gets_validation_error_when_harga_range_is_invalid(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.products.table', ['harga_min' => 50000, 'harga_max' => 10000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['harga_min']);
    }

    private function admin(): User
    {
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'admin']);
        return $user;
    }
}
