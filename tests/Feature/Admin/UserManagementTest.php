<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Access Control Tests
    // =========================================================================

    /** @test */
    public function guest_cannot_access_user_management(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function signer_cannot_access_user_management(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_access_user_management(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_user_management(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_access_user_management(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertStatus(200);
    }

    // =========================================================================
    // Admin Only Sees Their Department's Users
    // =========================================================================

    /** @test */
    public function admin_only_sees_users_from_their_department(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();

        $admin = User::factory()->admin()->withDepartment($dept1->id)->create();
        $userInDept1 = User::factory()->signer()->withDepartment($dept1->id)->create(['name' => 'Signer Dept 1']);
        $userInDept2 = User::factory()->signer()->withDepartment($dept2->id)->create(['name' => 'Signer Dept 2']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertSee('Signer Dept 1')
            ->assertDontSee('Signer Dept 2');
    }

    /** @test */
    public function super_admin_sees_all_users(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $user1 = User::factory()->signer()->withDepartment($dept1->id)->create(['name' => 'User Alpha']);
        $user2 = User::factory()->signer()->withDepartment($dept2->id)->create(['name' => 'User Beta']);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertSee('User Alpha')
            ->assertSee('User Beta');
    }

    // =========================================================================
    // Create User Tests
    // =========================================================================

    /** @test */
    public function admin_can_create_user_in_their_department(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();

        // Note: StoreUserRequest uses email:rfc (no DNS) in testing env
        // Password must meet: min 8, mixed case, numbers, symbols
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Signer',
            'email' => 'newsigner@testdomain.test',
            'password' => 'Password123!',
            'role' => 'signer',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newsigner@testdomain.test',
            'role' => 'signer',
            'department_id' => $dept->id,
        ]);
    }

    /** @test */
    public function admin_cannot_create_admin_role_user(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'department_id' => $dept->id,
            'status' => 'active',
        ])->assertSessionHasErrors('role');
    }

    /** @test */
    public function signer_cannot_create_user(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->post(route('users.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'signer',
                'status' => 'active',
            ])
            ->assertStatus(403);
    }

    // =========================================================================
    // Delete User Tests
    // =========================================================================

    /** @test */
    public function admin_can_delete_signer_in_their_department(): void
    {
        $dept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $signer))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $signer->id]);
    }

    /** @test */
    public function admin_cannot_delete_user_in_different_department(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dept1->id)->create();
        $signer = User::factory()->signer()->withDepartment($dept2->id)->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $signer))
            ->assertStatus(403);
    }

    /** @test */
    public function user_cannot_delete_themselves(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('users.destroy', $superAdmin))
            ->assertStatus(403);
    }
}
