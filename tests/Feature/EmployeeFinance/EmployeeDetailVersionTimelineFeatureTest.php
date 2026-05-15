<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EmployeeDetailVersionTimelineFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_employee_detail_page_and_see_versioned_timeline(): void
    {
        $employeeId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Asyraf Timeline Update',
            'phone' => '081299999999',
            'default_salary_amount' => 5500000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'inactive',
            'started_at' => '2026-04-01',
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_versions')->insert([
            [
                'id' => (string) Str::uuid(),
                'employee_id' => $employeeId,
                'revision_no' => 1,
                'event_name' => 'employee_created',
                'changed_by_actor_id' => null,
                'change_reason' => null,
                'changed_at' => '2026-04-01 08:00:00',
                'snapshot_json' => json_encode([
                    'employee_name' => 'Asyraf Timeline',
                    'phone' => '081211111111',
                    'salary_basis_type' => 'weekly',
                    'default_salary_amount' => 5000000,
                    'employment_status' => 'active',
                    'started_at' => '2026-04-01',
                    'ended_at' => null,
                ], JSON_THROW_ON_ERROR),
            ],
            [
                'id' => (string) Str::uuid(),
                'employee_id' => $employeeId,
                'revision_no' => 2,
                'event_name' => 'employee_deactivated',
                'changed_by_actor_id' => 'admin-1',
                'change_reason' => 'Koreksi kontak dan status kerja.',
                'changed_at' => '2026-04-10 09:30:00',
                'snapshot_json' => json_encode([
                    'employee_name' => 'Asyraf Timeline Update',
                    'phone' => '081299999999',
                    'salary_basis_type' => 'monthly',
                    'default_salary_amount' => 5500000,
                    'employment_status' => 'inactive',
                    'started_at' => '2026-04-01',
                    'ended_at' => null,
                ], JSON_THROW_ON_ERROR),
            ],
        ]);

        $response = $this->actingAs($this->createUserWithRole('admin-employee-timeline@example.test', 'admin'))
            ->get(route('admin.employees.show', ['employeeId' => $employeeId]));

        $response->assertOk();
        $response->assertSee('Ringkasan Karyawan');
        $response->assertSee('Identitas Saat Ini');
        $response->assertSee('Identitas Awal');
        $response->assertSee('Riwayat Versi Karyawan');
        $response->assertSee('Versi Awal');
        $response->assertSee('Asyraf Timeline Update');
        $response->assertSee('Asyraf Timeline');
        $response->assertSee('Revisi 2');
        $response->assertSee('Revisi 1');
        $response->assertSee('Karyawan dinonaktifkan');
        $response->assertSee('Karyawan dibuat');
        $response->assertSee('Koreksi kontak dan status kerja.');
        $response->assertSee('Actor admin-1');
        $response->assertSee('Rp5.500.000');
        $response->assertSee('Rp5.000.000');
        $response->assertSee('Nonaktif');
        $response->assertSee('Aktif');
    }

    public function test_employee_detail_marks_first_recorded_version_when_created_version_is_missing(): void
    {
        $employeeId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Seed Lama Update',
            'phone' => '081288888888',
            'default_salary_amount' => 4500000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'started_at' => '2026-03-01',
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_versions')->insert([
            'id' => (string) Str::uuid(),
            'employee_id' => $employeeId,
            'revision_no' => 3,
            'event_name' => 'employee_updated',
            'changed_by_actor_id' => 'admin-seed',
            'change_reason' => 'Sinkronisasi awal histori.',
            'changed_at' => '2026-04-05 11:00:00',
            'snapshot_json' => json_encode([
                'employee_name' => 'Seed Lama Update',
                'phone' => '081288888888',
                'salary_basis_type' => 'monthly',
                'default_salary_amount' => 4500000,
                'employment_status' => 'active',
                'started_at' => '2026-03-01',
                'ended_at' => null,
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $this->actingAs($this->createUserWithRole('admin-employee-seed@example.test', 'admin'))
            ->get(route('admin.employees.show', ['employeeId' => $employeeId]));

        $response->assertOk();
        $response->assertSee('Versi Tercatat Pertama');
        $response->assertSee('Data awal resmi tidak tersedia. Yang ditampilkan adalah versi pertama yang berhasil terekam di histori.');
        $response->assertSee('Seed Lama Update');
        $response->assertSee('Sinkronisasi awal histori.');
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
