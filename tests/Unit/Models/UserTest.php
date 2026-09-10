<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Role Helper Tests
    // =========================================================================

    /** @test */
    public function it_identifies_super_admin_role(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin()); // super_admin juga merupakan admin
        $this->assertFalse($user->isOperator());
        $this->assertFalse($user->isSigner());
    }

    /** @test */
    public function it_identifies_admin_role(): void
    {
        $user = User::factory()->admin()->create();
        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isOperator());
        $this->assertFalse($user->isSigner());
    }

    /** @test */
    public function it_identifies_operator_role(): void
    {
        $user = User::factory()->operator()->create();
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isOperator());
        $this->assertFalse($user->isSigner());
    }

    /** @test */
    public function it_identifies_signer_role(): void
    {
        $user = User::factory()->signer()->create();
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOperator());
        $this->assertTrue($user->isSigner());
    }

    // =========================================================================
    // Status Tests
    // =========================================================================

    /** @test */
    public function it_identifies_active_status(): void
    {
        $activeUser = User::factory()->active()->create();
        $inactiveUser = User::factory()->inactive()->create();

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    // =========================================================================
    // canManageDepartment Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_manage_any_department(): void
    {
        $dept = Department::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($superAdmin->canManageDepartment($dept->id));
    }

    /** @test */
    public function admin_can_manage_only_assigned_department(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();
        $admin = User::factory()->admin()->create();

        // Assign dept1 to admin
        $admin->managedDepartments()->attach($dept1->id);

        $this->assertTrue($admin->canManageDepartment($dept1->id));
        $this->assertFalse($admin->canManageDepartment($dept2->id));
    }

    /** @test */
    public function operator_cannot_manage_any_department(): void
    {
        $dept = Department::factory()->create();
        $operator = User::factory()->operator()->create();

        $this->assertFalse($operator->canManageDepartment($dept->id));
    }

    // =========================================================================
    // getManagedDepartmentIds Tests
    // =========================================================================

    /** @test */
    public function super_admin_gets_all_department_ids(): void
    {
        Department::factory()->count(3)->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $ids = $superAdmin->getManagedDepartmentIds();
        $this->assertCount(3, $ids);
    }

    /** @test */
    public function admin_gets_only_their_managed_department_ids(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();
        $admin = User::factory()->admin()->create();

        $admin->managedDepartments()->attach($dept1->id);

        $ids = $admin->getManagedDepartmentIds();
        $this->assertContains($dept1->id, $ids);
        $this->assertNotContains($dept2->id, $ids);
    }

    // =========================================================================
    // Soft Delete Tests
    // =========================================================================

    /** @test */
    public function user_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId)->deleted_at);
    }
}
