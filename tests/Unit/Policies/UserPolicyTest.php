<?php

namespace Tests\Unit\Policies;

use App\Models\Department;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    // =========================================================================
    // viewAny() Tests
    // =========================================================================

    /** @test */
    public function admin_can_view_any_user(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $operator = User::factory()->operator()->create();
        $signer = User::factory()->signer()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($superAdmin));
        $this->assertFalse($this->policy->viewAny($operator));
        $this->assertFalse($this->policy->viewAny($signer));
    }

    // =========================================================================
    // view() Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $targetUser = User::factory()->operator()->create();

        $this->assertTrue($this->policy->view($superAdmin, $targetUser));
    }

    /** @test */
    public function admin_can_view_user_in_same_department(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $targetUser = User::factory()->operator()->withDepartment($dept->id)->create();

        $this->assertTrue($this->policy->view($admin, $targetUser));
    }

    /** @test */
    public function admin_cannot_view_user_in_different_department(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept1->id)->create();
        $targetUser = User::factory()->operator()->withDepartment($dept2->id)->create();

        $this->assertFalse($this->policy->view($admin, $targetUser));
    }

    /** @test */
    public function user_can_view_themselves(): void
    {
        $user = User::factory()->signer()->create();
        $this->assertTrue($this->policy->view($user, $user));
    }

    // =========================================================================
    // create() Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_always_create_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->assertTrue($this->policy->create($superAdmin));
    }

    /** @test */
    public function admin_with_department_can_create_user(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->assertTrue($this->policy->create($admin));
    }

    /** @test */
    public function admin_without_department_cannot_create_user(): void
    {
        $admin = User::factory()->admin()->create(); // no department

        $this->assertFalse($this->policy->create($admin));
    }

    /** @test */
    public function operator_cannot_create_user(): void
    {
        $operator = User::factory()->operator()->create();
        $this->assertFalse($this->policy->create($operator));
    }

    // =========================================================================
    // createRole() Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_create_any_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertTrue($this->policy->createRole($superAdmin, 'admin'));
        $this->assertTrue($this->policy->createRole($superAdmin, 'super_admin'));
        $this->assertTrue($this->policy->createRole($superAdmin, 'operator'));
        $this->assertTrue($this->policy->createRole($superAdmin, 'signer'));
    }

    /** @test */
    public function admin_can_only_create_operator_or_signer(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($this->policy->createRole($admin, 'admin'));
        $this->assertFalse($this->policy->createRole($admin, 'super_admin'));
        $this->assertTrue($this->policy->createRole($admin, 'operator'));
        $this->assertTrue($this->policy->createRole($admin, 'signer'));
    }

    // =========================================================================
    // update() Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_update_anyone(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $target = User::factory()->signer()->create();

        $this->assertTrue($this->policy->update($superAdmin, $target));
    }

    /** @test */
    public function admin_cannot_update_super_admin_or_other_admin(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $otherAdmin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->assertFalse($this->policy->update($admin, $superAdmin));
        $this->assertFalse($this->policy->update($admin, $otherAdmin));
    }

    /** @test */
    public function admin_can_update_signer_in_same_department(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();

        $this->assertTrue($this->policy->update($admin, $signer));
    }

    // =========================================================================
    // delete() Tests
    // =========================================================================

    /** @test */
    public function user_cannot_delete_themselves(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->assertFalse($this->policy->delete($superAdmin, $superAdmin));
    }

    /** @test */
    public function only_super_admin_can_delete_admin(): void
    {
        $dept = Department::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $targetAdmin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->assertTrue($this->policy->delete($superAdmin, $admin));
        $this->assertFalse($this->policy->delete($admin, $targetAdmin));
    }

    /** @test */
    public function admin_can_delete_signer_in_same_department(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();

        $this->assertTrue($this->policy->delete($admin, $signer));
    }
}
