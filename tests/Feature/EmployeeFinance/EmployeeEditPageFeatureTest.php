<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EmployeeEditPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_edit_employee_page(): void
    {
        $this->get(route('admin.employees.edit', ['employeeId' => 'employee-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_edit_employee_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('kasir-employee-edit@example.test', 'kasir'))
            ->get(route('admin.employees.edit', ['employeeId' => 'employee-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_edit_employee_page(): void
    {
        $employeeId = $this->seedEmployee();

        $response = $this->actingAs($this->createUserWithRole('admin-employee-edit@example.test', 'admin'))
            ->get(route('admin.employees.edit', ['employeeId' => $employeeId]));

        $response->assertOk();
        $response->assertSee('Edit Karyawan');
        $response->assertSee('Catatan Perubahan');
        $response->assertSee('Nama Karyawan');
        $response->assertSee('Basis Gaji');
        $response->assertSee('Default Gaji');
        $response->assertSee('Status');
        $response->assertSee('Nonaktif');
        $response->assertSee('Simpan Perubahan');
    }

    public function test_admin_is_redirected_to_index_when_employee_is_missing(): void
    {
        $response = $this->actingAs($this->createUserWithRole('admin-employee-missing@example.test', 'admin'))
            ->get(route('admin.employees.edit', ['employeeId' => 'missing-employee']));

        $response->assertRedirect(route('admin.employees.index'));
        $response->assertSessionHas('error', 'Data karyawan tidak ditemukan.');
    }

    public function test_admin_can_update_employee_and_write_versioned_history_records(): void
    {
        $employeeId = $this->seedEmployee();
        $admin = $this->createUserWithRole('admin-employee-update@example.test', 'admin');

        $response = $this->actingAs($admin)
            ->put(route('admin.employees.update', ['employeeId' => $employeeId]), [
                'employee_name' => 'Budi Santoso Update',
                'phone' => '081299999999',
                'default_salary_amount' => 5500000,
                'salary_basis_type' => 'monthly',
                'employment_status' => 'inactive',
                'started_at' => null,
                'ended_at' => null,
                'change_reason' => 'Cuti panjang sementara.',
            ]);

        $response->assertRedirect(route('admin.employees.index'));
        $response->assertSessionHas('success', 'Data karyawan berhasil diperbarui.');

        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'employee_name' => 'Budi Santoso Update',
            'phone' => '081299999999',
            'default_salary_amount' => 5500000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'inactive',
        ]);

        $this->assertDatabaseHas('employee_versions', [
            'employee_id' => $employeeId,
            'revision_no' => 1,
            'event_name' => 'employee_deactivated',
            'changed_by_actor_id' => (string) $admin->getAuthIdentifier(),
            'change_reason' => 'Cuti panjang sementara.',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'aggregate_type' => 'employee',
            'aggregate_id' => $employeeId,
            'event_name' => 'employee_deactivated',
            'bounded_context' => 'employee_finance',
            'actor_id' => (string) $admin->getAuthIdentifier(),
            'actor_role' => 'admin',
            'reason' => 'Cuti panjang sementara.',
            'source_channel' => 'admin_web',
        ]);

        $auditEventId = (string) DB::table('audit_events')
            ->where('aggregate_type', 'employee')
            ->where('aggregate_id', $employeeId)
            ->where('event_name', 'employee_deactivated')
            ->value('id');

        $this->assertNotSame('', $auditEventId);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => $auditEventId,
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => $auditEventId,
            'snapshot_kind' => 'after',
        ]);

        $beforePayload = (string) DB::table('audit_event_snapshots')
            ->where('audit_event_id', $auditEventId)
            ->where('snapshot_kind', 'before')
            ->value('payload_json');

        $afterPayload = (string) DB::table('audit_event_snapshots')
            ->where('audit_event_id', $auditEventId)
            ->where('snapshot_kind', 'after')
            ->value('payload_json');

        $beforeSnapshot = json_decode($beforePayload, true, 512, JSON_THROW_ON_ERROR);
        $afterSnapshot = json_decode($afterPayload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Budi Santoso', $beforeSnapshot['employee_name']);
        $this->assertSame('081211111111', $beforeSnapshot['phone']);
        $this->assertSame('weekly', $beforeSnapshot['salary_basis_type']);
        $this->assertSame(5000000, $beforeSnapshot['default_salary_amount']);
        $this->assertSame('active', $beforeSnapshot['employment_status']);
        $this->assertNull($beforeSnapshot['started_at']);
        $this->assertNull($beforeSnapshot['ended_at']);

        $this->assertSame('Budi Santoso Update', $afterSnapshot['employee_name']);
        $this->assertSame('081299999999', $afterSnapshot['phone']);
        $this->assertSame('monthly', $afterSnapshot['salary_basis_type']);
        $this->assertSame(5500000, $afterSnapshot['default_salary_amount']);
        $this->assertSame('inactive', $afterSnapshot['employment_status']);
        $this->assertNull($afterSnapshot['started_at']);
        $this->assertNull($afterSnapshot['ended_at']);

        $version = DB::table('employee_versions')
            ->where('employee_id', $employeeId)
            ->where('revision_no', 1)
            ->first();

        $this->assertNotNull($version);

        $versionSnapshot = json_decode((string) $version->snapshot_json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Budi Santoso Update', $versionSnapshot['employee_name']);
        $this->assertSame('081299999999', $versionSnapshot['phone']);
        $this->assertSame('monthly', $versionSnapshot['salary_basis_type']);
        $this->assertSame(5500000, $versionSnapshot['default_salary_amount']);
        $this->assertSame('inactive', $versionSnapshot['employment_status']);
        $this->assertNull($versionSnapshot['started_at']);
        $this->assertNull($versionSnapshot['ended_at']);

        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'employee_profile_updated',
        ]);
    }

    public function test_admin_cannot_update_employee_when_change_reason_is_blank(): void
    {
        $employeeId = $this->seedEmployee();

        $response = $this->from(route('admin.employees.edit', ['employeeId' => $employeeId]))
            ->actingAs($this->createUserWithRole('admin-employee-update-blank@example.test', 'admin'))
            ->put(route('admin.employees.update', ['employeeId' => $employeeId]), [
                'employee_name' => 'Budi Santoso Update',
                'phone' => '081299999999',
                'default_salary_amount' => 5500000,
                'salary_basis_type' => 'monthly',
                'employment_status' => 'inactive',
                'started_at' => null,
                'ended_at' => null,
                'change_reason' => '   ',
            ]);

        $response->assertRedirect(route('admin.employees.edit', ['employeeId' => $employeeId]));
        $response->assertSessionHasErrors([
            'employee' => 'Catatan perubahan wajib diisi.',
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employeeId,
            'employee_name' => 'Budi Santoso',
            'phone' => '081211111111',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'weekly',
            'employment_status' => 'active',
        ]);

        $this->assertDatabaseMissing('employee_versions', [
            'employee_id' => $employeeId,
        ]);

        $this->assertDatabaseMissing('audit_events', [
            'aggregate_type' => 'employee',
            'aggregate_id' => $employeeId,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'employee_profile_updated',
        ]);
    }

    private function seedEmployee(): string
    {
        $employeeId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Budi Santoso',
            'phone' => '081211111111',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'weekly',
            'employment_status' => 'active',
            'started_at' => null,
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $employeeId;
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
