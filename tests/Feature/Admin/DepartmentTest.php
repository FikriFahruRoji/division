<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Access Control — Only Super Admin
    // =========================================================================

    /** @test */
    public function guest_cannot_access_departments(): void
    {
        $this->get(route('departments.index'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_cannot_access_department_management(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->actingAs($admin)
            ->get(route('departments.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_access_department_management(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('departments.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function signer_cannot_access_department_management(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->get(route('departments.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_access_departments(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('departments.index'))
            ->assertStatus(200);
    }

    // =========================================================================
    // Create Department Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_create_department(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post(route('departments.store'), [
            'name' => 'Departemen IT',
            'code' => 'IT',
            'description' => 'Departemen teknologi informasi',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', [
            'name' => 'Departemen IT',
            'code' => 'IT',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function department_code_must_be_unique(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Department::factory()->create(['code' => 'IT']);

        $this->actingAs($superAdmin)->post(route('departments.store'), [
            'name' => 'Another IT',
            'code' => 'IT',
            'description' => 'Duplicate code test',
            'status' => 'active',
        ])->assertSessionHasErrors('code');
    }

    /** @test */
    public function department_code_must_be_uppercase_alphanumeric(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('departments.store'), [
            'name' => 'Department Test',
            'code' => 'lowercase code!',
            'description' => 'Invalid code',
            'status' => 'active',
        ])->assertSessionHasErrors('code');
    }

    // =========================================================================
    // Delete Department Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_delete_empty_department(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $dept = Department::factory()->create();

        $this->actingAs($superAdmin)
            ->delete(route('departments.destroy', $dept))
            ->assertRedirect(route('departments.index'));

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    /** @test */
    public function cannot_delete_department_with_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $dept = Department::factory()->create();
        User::factory()->signer()->withDepartment($dept->id)->create();

        $this->actingAs($superAdmin)
            ->delete(route('departments.destroy', $dept))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }
}
