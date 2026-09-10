<?php

namespace Tests\Unit\Policies;

use App\Models\Department;
use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DocumentPolicy();
    }

    // =========================================================================
    // view() Tests
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_document(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $doc = Document::factory()->create();

        $this->assertTrue($this->policy->view($superAdmin, $doc));
    }

    /** @test */
    public function admin_can_view_document_in_same_department(): void
    {
        $dept = Department::factory()->create();
        $creator = User::factory()->operator()->withDepartment($dept->id)->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();
        $doc = Document::factory()->withCreator($creator)->create();

        $this->assertTrue($this->policy->view($admin, $doc));
    }

    /** @test */
    public function admin_cannot_view_document_in_different_department(): void
    {
        $dept1 = Department::factory()->create();
        $dept2 = Department::factory()->create();
        $creator = User::factory()->operator()->withDepartment($dept1->id)->create();
        $admin = User::factory()->admin()->withDepartment($dept2->id)->create();
        $doc = Document::factory()->withCreator($creator)->create();

        $this->assertFalse($this->policy->view($admin, $doc));
    }

    /** @test */
    public function creator_can_view_own_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->withCreator($creator)->create();

        $this->assertTrue($this->policy->view($creator, $doc));
    }

    /** @test */
    public function assigned_signer_can_view_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->assertTrue($this->policy->view($signer, $doc));
    }

    /** @test */
    public function unassigned_signer_cannot_view_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        $this->assertFalse($this->policy->view($signer, $doc));
    }

    // =========================================================================
    // update() Tests
    // =========================================================================

    /** @test */
    public function only_draft_or_rejected_documents_can_be_updated(): void
    {
        $creator = User::factory()->operator()->create();
        $draftDoc = Document::factory()->draft()->withCreator($creator)->create();
        $pendingDoc = Document::factory()->pendingSignature()->withCreator($creator)->create();
        $signedDoc = Document::factory()->signedValid()->withCreator($creator)->create();

        $this->assertTrue($this->policy->update($creator, $draftDoc));
        $this->assertFalse($this->policy->update($creator, $pendingDoc));
        $this->assertFalse($this->policy->update($creator, $signedDoc));
    }

    /** @test */
    public function super_admin_can_update_any_draft_document(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $doc = Document::factory()->draft()->create();

        $this->assertTrue($this->policy->update($superAdmin, $doc));
    }

    // =========================================================================
    // delete() Tests
    // =========================================================================

    /** @test */
    public function signed_valid_document_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $doc = Document::factory()->signedValid()->create();

        $this->assertFalse($this->policy->delete($superAdmin, $doc));
    }

    /** @test */
    public function creator_can_delete_own_draft_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->draft()->withCreator($creator)->create();

        $this->assertTrue($this->policy->delete($creator, $doc));
    }

    // =========================================================================
    // sign() Tests
    // =========================================================================

    /** @test */
    public function assigned_signer_can_sign_pending_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->assertTrue($this->policy->sign($signer, $doc));
    }

    /** @test */
    public function cannot_sign_draft_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->draft()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->assertFalse($this->policy->sign($signer, $doc));
    }

    /** @test */
    public function unassigned_user_cannot_sign_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        $this->assertFalse($this->policy->sign($signer, $doc));
    }

    // =========================================================================
    // revoke() Tests
    // =========================================================================

    /** @test */
    public function only_signed_valid_document_can_be_revoked(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $draftDoc = Document::factory()->draft()->create();
        $signedDoc = Document::factory()->signedValid()->create();

        $this->assertFalse($this->policy->revoke($superAdmin, $draftDoc));
        $this->assertTrue($this->policy->revoke($superAdmin, $signedDoc));
    }

    /** @test */
    public function creator_can_revoke_their_signed_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->signedValid()->withCreator($creator)->create();

        $this->assertTrue($this->policy->revoke($creator, $doc));
    }

    /** @test */
    public function signed_signer_can_revoke_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->signedValid()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'signed',
        ]);

        $this->assertTrue($this->policy->revoke($signer, $doc));
    }

    /** @test */
    public function unsigned_signer_cannot_revoke_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->signedValid()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'pending',
        ]);

        $this->assertFalse($this->policy->revoke($signer, $doc));
    }
}
