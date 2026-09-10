<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_audit_logs(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('audit-logs.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function signer_cannot_access_audit_logs(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->get(route('audit-logs.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_access_audit_logs(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('audit-logs.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_audit_logs(): void
    {
        $this->get(route('audit-logs.index'))
            ->assertRedirect(route('login'));
    }
}
