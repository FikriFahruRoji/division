<?php

namespace Tests\Feature\Policies;

use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminFekon;
    protected User $adminRek;
    protected User $operatorFekon;
    protected User $signerFekon;
    protected User $signerRek;
    protected Department $deptFekon;
    protected Department $deptRek;
    protected Document $docFekon;
    protected Document $docRek;

    protected function setUp(): void
    {
        parent::setUp();

        // Create departments
        $this->deptFekon = Department::factory()->create(['code' => 'FEKON', 'name' => 'Fakultas Ekonomi']);
        $this->deptRek = Department::factory()->create(['code' => 'REK', 'name' => 'Rektorat']);

        // Create users
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'department_id' => $this->deptFekon->id,
        ]);

        $this->adminFekon = User::factory()->create([
            'role' => 'admin',
            'department_id' => $this->deptFekon->id,
        ]);

        $this->adminRek = User::factory()->create([
            'role' => 'admin',
            'department_id' => $this->deptRek->id,
        ]);

        $this->operatorFekon = User::factory()->create([
            'role' => 'operator',
            'department_id' => $this->deptFekon->id,
        ]);

        $this->signerFekon = User::factory()->create([
            'role' => 'signer',
            'department_id' => $this->deptFekon->id,
        ]);

        $this->signerRek = User::factory()->create([
            'role' => 'signer',
            'department_id' => $this->deptRek->id,
        ]);

        // Create documents
        $this->docFekon = Document::factory()->create([
            'creator_id' => $this->operatorFekon->id,
            'status' => 'draft',
        ]);

        $this->docRek = Document::factory()->create([
            'creator_id' => $this->adminRek->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function super_admin_can_view_any_document(): void
    {
        $this->assertTrue($this->superAdmin->can('view', $this->docFekon));
        $this->assertTrue($this->superAdmin->can('view', $this->docRek));
    }

    /** @test */
    public function admin_can_view_documents_in_their_department(): void
    {
        $this->assertTrue($this->adminFekon->can('view', $this->docFekon));
    }

    /** @test */
    public function admin_cannot_view_documents_in_other_department(): void
    {
        $this->assertFalse($this->adminFekon->can('view', $this->docRek));
        $this->assertFalse($this->adminRek->can('view', $this->docFekon));
    }

    /** @test */
    public function creator_can_view_their_own_document(): void
    {
        $this->assertTrue($this->operatorFekon->can('view', $this->docFekon));
    }

    /** @test */
    public function super_admin_can_update_any_draft_document(): void
    {
        $this->assertTrue($this->superAdmin->can('update', $this->docFekon));
        $this->assertTrue($this->superAdmin->can('update', $this->docRek));
    }

    /** @test */
    public function admin_can_update_draft_documents_in_their_department(): void
    {
        $this->assertTrue($this->adminFekon->can('update', $this->docFekon));
    }

    /** @test */
    public function admin_cannot_update_documents_in_other_department(): void
    {
        $this->assertFalse($this->adminFekon->can('update', $this->docRek));
    }

    /** @test */
    public function super_admin_can_delete_non_signed_documents(): void
    {
        $this->assertTrue($this->superAdmin->can('delete', $this->docFekon));
        $this->assertTrue($this->superAdmin->can('delete', $this->docRek));
    }

    /** @test */
    public function admin_cannot_delete_documents_in_other_department(): void
    {
        $this->assertFalse($this->adminFekon->can('delete', $this->docRek));
    }
}
