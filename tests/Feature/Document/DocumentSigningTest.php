<?php

namespace Tests\Feature\Document;

use App\Models\Department;
use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Models\VerificationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature Tests — Document Signing Flow (Prioritas Utama SQA)
 *
 * Test Coverage:
 * - Finalisasi dokumen oleh admin/operator
 * - Approve (tanda tangan) oleh signer
 * - Reject (penolakan) oleh signer
 * - Otorisasi role-based
 * - Status transitions
 */
class DocumentSigningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    // =========================================================================
    // Finalize Document Tests
    // =========================================================================

    /** @test */
    public function operator_can_finalize_draft_document_with_signers(): void
    {
        $dept = Department::factory()->create();
        $operator = User::factory()->operator()->withDepartment($dept->id)->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();

        // Create the file in fake storage so hash_file() succeeds during finalize
        $filePath = 'documents/secure/test-finalize.pdf';
        Storage::disk('local')->put($filePath, "%PDF-1.4 fake content for finalize test");

        $doc = Document::factory()->draft()->withCreator($operator)->create([
            'file_path' => $filePath,
        ]);

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($operator)
            ->post(route('documents.finalize', $doc))
            ->assertRedirect(route('documents.show', $doc));

        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'pending_signature',
        ]);
    }

    /** @test */
    public function cannot_finalize_document_without_signers(): void
    {
        $operator = User::factory()->operator()->create();
        $doc = Document::factory()->draft()->withCreator($operator)->create();

        $this->actingAs($operator)
            ->post(route('documents.finalize', $doc))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Status harus tetap draft
        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function signer_cannot_finalize_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->draft()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($signer)
            ->post(route('documents.finalize', $doc))
            ->assertStatus(403);
    }

    /** @test */
    public function pending_signature_document_cannot_be_re_finalized_by_non_admin(): void
    {
        $operator = User::factory()->operator()->create();
        $doc = Document::factory()->pendingSignature()->withCreator($operator)->create();

        $this->actingAs($operator)
            ->post(route('documents.finalize', $doc))
            ->assertStatus(403);
    }

    // =========================================================================
    // Pending Signature List Tests
    // =========================================================================

    /** @test */
    public function signer_can_view_their_pending_documents(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->actingAs($signer)
            ->get(route('signatures.pending'))
            ->assertStatus(200)
            ->assertSee($doc->doc_number);
    }

    /** @test */
    public function signer_does_not_see_documents_not_assigned_to_them(): void
    {
        $signer1 = User::factory()->signer()->create();
        $signer2 = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer1->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->actingAs($signer2)
            ->get(route('signatures.pending'))
            ->assertStatus(200)
            ->assertDontSee($doc->title);
    }

    // =========================================================================
    // Reject Document Tests
    // =========================================================================

    /** @test */
    public function assigned_signer_can_reject_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->actingAs($signer)
            ->post(route('signatures.reject', $doc), [
                'reason' => 'Dokumen perlu direvisi lebih lanjut.',
            ])
            ->assertRedirect(route('signatures.pending'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('signer_assignments', [
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'status' => 'rejected',
            'rejection_reason' => 'Dokumen perlu direvisi lebih lanjut.',
        ]);
    }

    /** @test */
    public function rejection_requires_reason(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);

        $this->actingAs($signer)
            ->post(route('signatures.reject', $doc), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    /** @test */
    public function unassigned_signer_cannot_reject_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->pendingSignature()->create();

        $this->actingAs($signer)
            ->post(route('signatures.reject', $doc), [
                'reason' => 'Alasan penolakan',
            ])
            ->assertStatus(403);
    }

    // =========================================================================
    // Revoke Document Tests
    // =========================================================================

    /** @test */
    public function creator_can_revoke_signed_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->signedValid()->withCreator($creator)->create();

        $this->actingAs($creator)
            ->post(route('documents.revoke', $doc), [
                'reason' => 'Dokumen perlu ditarik kembali.',
            ])
            ->assertRedirect(route('documents.show', $doc));

        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'revoked',
        ]);
    }

    /** @test */
    public function signer_who_signed_can_revoke_document(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->signedValid()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'signed',
        ]);

        $this->actingAs($signer)
            ->post(route('documents.revoke', $doc), [
                'reason' => 'Signer mencabut tanda tangannya.',
            ])
            ->assertRedirect(route('documents.show', $doc));

        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'status' => 'revoked',
        ]);
    }

    /** @test */
    public function cannot_revoke_draft_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->draft()->withCreator($creator)->create();

        $this->actingAs($creator)
            ->post(route('documents.revoke', $doc), [
                'reason' => 'Alasan pencabutan',
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function authorized_user_can_download_document(): void
    {
        $creator = User::factory()->operator()->create();
        $filePath = 'documents/test_download.pdf';
        Storage::disk('local')->put($filePath, "%PDF-1.4 fake content for download");

        $doc = Document::factory()->signedValid()->withCreator($creator)->create([
            'file_path' => $filePath,
            'signed_file_path' => $filePath,
        ]);

        $this->actingAs($creator)
            ->get(route('documents.download', $doc))
            ->assertStatus(200);
    }

    // =========================================================================
    // Signature History Tests
    // =========================================================================

    /** @test */
    public function signer_can_view_their_signature_history(): void
    {
        $signer = User::factory()->signer()->create();
        $doc = Document::factory()->signedValid()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'signed',
        ]);

        $this->actingAs($signer)
            ->get(route('signatures.history'))
            ->assertStatus(200)
            ->assertSee($doc->title);
    }
}
