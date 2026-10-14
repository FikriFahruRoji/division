<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Services\SigningService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReauditRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createDocWithFiles(array $attributes = []): Document
    {
        $doc = Document::factory()->create($attributes);
        $syntheticContent = "%PDF-1.4 Synthetic Original Content " . $doc->id;
        Storage::disk('local')->put($doc->file_path, $syntheticContent);
        $doc->update(['hash' => hash('sha256', $syntheticContent)]);

        if ($doc->signed_file_path) {
            $signedContent = "%PDF-1.4 Synthetic Signed Content " . $doc->id;
            Storage::disk('local')->put($doc->signed_file_path, $signedContent);
            $doc->update(['hash' => hash('sha256', $signedContent)]);
        }

        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => $doc->isSignedValid() ? 'valid' : 'draft',
        ]);

        return $doc;
    }

    /**
     * RA-01: Confidential metadata and signers are masked for unauthorized guests and users.
     */
    public function test_confidential_metadata_and_signers_are_masked_for_unauthorized_viewers(): void
    {
        $depA = Department::factory()->create();
        $depB = Department::factory()->create();
        $owner = User::factory()->operator()->withDepartment($depA->id)->create();
        $signer = User::factory()->signer()->withDepartment($depA->id)->create([
            'name' => 'ConfidentialSignerA',
            'position' => 'ConfidentialPositionA',
        ]);

        $doc = $this->createDocWithFiles([
            'creator_id' => $owner->id,
            'department_id' => $depA->id,
            'classification' => 'rahasia',
            'title' => 'SuperSecretDocumentTitle',
            'unit' => 'SecretUnitCode',
            'doc_number' => 'SECRET-DOC-999',
        ]);

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
        ]);

        // 1. Guest viewer check
        $guestResponse = $this->get(route('verify', $doc->qr_token));
        $guestResponse->assertOk()
            ->assertDontSee('SuperSecretDocumentTitle')
            ->assertDontSee('SecretUnitCode')
            ->assertDontSee('SECRET-DOC-999')
            ->assertDontSee('ConfidentialSignerA')
            ->assertDontSee('ConfidentialPositionA')
            ->assertSee('Dokumen Terbatas');

        // 2. Unrelated logged-in operator check
        $unrelatedOperator = User::factory()->operator()->withDepartment($depB->id)->create();
        $unrelatedResponse = $this->actingAs($unrelatedOperator)->get(route('verify', $doc->qr_token));
        $unrelatedResponse->assertOk()
            ->assertDontSee('SuperSecretDocumentTitle')
            ->assertDontSee('SecretUnitCode')
            ->assertDontSee('SECRET-DOC-999')
            ->assertDontSee('ConfidentialSignerA')
            ->assertDontSee('ConfidentialPositionA')
            ->assertSee('Dokumen Terbatas');

        // 3. Authorized owner check
        $ownerResponse = $this->actingAs($owner)->get(route('verify', $doc->qr_token));
        $ownerResponse->assertOk()
            ->assertSee('SuperSecretDocumentTitle')
            ->assertSee('SecretUnitCode')
            ->assertSee('SECRET-DOC-999')
            ->assertSee('ConfidentialSignerA');
    }

    /**
     * RA-02: Operator only sees own documents or documents assigned to them.
     */
    public function test_operator_cannot_read_peer_metadata_without_assignment(): void
    {
        $dep = Department::factory()->create();
        $op1 = User::factory()->operator()->withDepartment($dep->id)->create();
        $op2 = User::factory()->operator()->withDepartment($dep->id)->create(['email' => 'peer-secret@example.test']);

        $peerDoc = Document::factory()->create([
            'creator_id' => $op2->id,
            'department_id' => $dep->id,
            'title' => 'PeerSecretDocumentTitle',
        ]);

        // op1 has NO assignment on peerDoc
        $this->actingAs($op1)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('PeerSecretDocumentTitle')
            ->assertDontSee('peer-secret@example.test');

        $this->actingAs($op1)->get('/documents')
            ->assertOk()
            ->assertDontSee('PeerSecretDocumentTitle')
            ->assertDontSee('peer-secret@example.test');

        // Assign op1 as signer on another document -> op1 must see this one
        $assignedDoc = Document::factory()->create([
            'creator_id' => $op2->id,
            'department_id' => $dep->id,
            'title' => 'AssignedDocumentTitle',
        ]);
        SignerAssignment::factory()->create([
            'document_id' => $assignedDoc->id,
            'signer_id' => $op1->id,
        ]);

        $this->actingAs($op1)->get('/documents')
            ->assertOk()
            ->assertSee('AssignedDocumentTitle');
    }

    /**
     * RA-03: Document list uses persistent document department_id.
     */
    public function test_document_list_uses_persistent_document_department(): void
    {
        $depA = Department::factory()->create();
        $depB = Department::factory()->create();
        $adminA = User::factory()->admin()->withDepartment($depA->id)->create();
        $adminB = User::factory()->admin()->withDepartment($depB->id)->create();

        $creator = User::factory()->operator()->withDepartment($depA->id)->create();
        $doc = Document::factory()->create([
            'creator_id' => $creator->id,
            'department_id' => $depA->id,
            'title' => 'FrozenDepartmentDocumentTitle',
        ]);

        // Creator transfers to department B
        $creator->update(['department_id' => $depB->id]);

        // Admin A still sees it
        $this->actingAs($adminA)->get('/dashboard')->assertOk()->assertSee('FrozenDepartmentDocumentTitle');
        $this->actingAs($adminA)->get('/documents')->assertOk()->assertSee('FrozenDepartmentDocumentTitle');

        // Admin B does NOT see it
        $this->actingAs($adminB)->get('/dashboard')->assertOk()->assertDontSee('FrozenDepartmentDocumentTitle');
        $this->actingAs($adminB)->get('/documents')->assertOk()->assertDontSee('FrozenDepartmentDocumentTitle');
    }

    /**
     * RA-07: Departmentless operator form fails closed.
     */
    public function test_departmentless_operator_form_fails_closed(): void
    {
        $op = User::factory()->operator()->create(['department_id' => null]);
        $dep = Department::factory()->create(['name' => 'ClassifiedResearchDepartment']);
        User::factory()->signer()->withDepartment($dep->id)->create(['name' => 'Classified Research Signer']);

        $response = $this->actingAs($op)->get('/documents/create');
        $response->assertOk()
            ->assertDontSee('ClassifiedResearchDepartment')
            ->assertDontSee('Classified Research Signer');
    }

    /**
     * RA-08: Inactive user cannot update password.
     */
    public function test_inactive_session_cannot_update_password(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $response = $this->actingAs($inactiveUser)->put('/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * RA-09: User detail view renders with HTTP 200 without error 500.
     */
    public function test_user_detail_view_renders_successfully(): void
    {
        $dep = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dep->id)->create();
        $targetUser = User::factory()->signer()->withDepartment($dep->id)->create([
            'name' => 'Dr. Test Signer',
            'nip_nidn' => '198501012020',
        ]);

        $response = $this->actingAs($admin)->get('/users/' . $targetUser->id);
        $response->assertOk()
            ->assertSee('Detail User')
            ->assertSee('Dr. Test Signer')
            ->assertSee('198501012020');
    }

    /**
     * RA-10: Verification captions are accurate per status.
     */
    public function test_verification_captions_are_accurate_per_status(): void
    {
        $draftDoc = $this->createDocWithFiles(['status' => 'draft']);
        $this->get(route('verify', $draftDoc->qr_token))
            ->assertOk()
            ->assertSee('Dokumen ini masih berstatus draft')
            ->assertDontSee('Dokumen ini telah dicabut');

        $rejectedDoc = $this->createDocWithFiles(['status' => 'rejected']);
        $this->get(route('verify', $rejectedDoc->qr_token))
            ->assertOk()
            ->assertSee('Dokumen ini telah ditolak')
            ->assertDontSee('Dokumen ini telah dicabut');
    }

    /**
     * RA-15: Newly created user automatically receives email verification notification.
     */
    public function test_admin_created_user_receives_email_verification_notification(): void
    {
        Notification::fake();

        $dep = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($dep->id)->create();

        $this->actingAs($admin)->post('/users', [
            'name' => 'New Staff Member',
            'email' => 'staff.new@example.test',
            'password' => 'StrongPass123!',
            'role' => 'operator',
            'department_id' => $dep->id,
            'status' => 'active',
        ])->assertRedirect('/users');

        $createdUser = User::where('email', 'staff.new@example.test')->firstOrFail();
        Notification::assertSentTo($createdUser, VerifyEmail::class);
    }

    /**
     * RA-05: Source file tampering before or between signatures is strictly rejected.
     */
    public function test_tampered_source_file_is_strictly_rejected_during_signing(): void
    {
        $dep = Department::factory()->create();
        $owner = User::factory()->operator()->withDepartment($dep->id)->create();
        $signer = User::factory()->signer()->withDepartment($dep->id)->create();

        $doc = $this->createDocWithFiles([
            'creator_id' => $owner->id,
            'department_id' => $dep->id,
            'status' => 'pending_signature',
        ]);

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'status' => 'pending',
        ]);

        // Deliberately tamper with the file content on disk
        Storage::disk('local')->put($doc->file_path, '%PDF-1.4 TAMPERED ADULTERATED CONTENT');

        $signingService = app(SigningService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Integritas berkas sumber telah berubah atau tidak sesuai dengan hash dokumen yang disetujui (RA-05).');

        $signingService->signDocument($doc, $signer);
    }

    /**
     * RA-06: Stale finalize cannot reset already active/completed documents.
     */
    public function test_stale_finalize_is_rejected_on_non_draft_document(): void
    {
        $doc = $this->createDocWithFiles(['status' => 'signed_valid']);

        $signingService = app(SigningService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dokumen tidak dapat diajukan karena status saat ini bukan draft atau perbaikan');

        $signingService->finalizeDocument($doc);
    }
    /**
     * Test document summary can be stored and displayed on detail page.
     */
    public function test_document_summary_can_be_stored_and_viewed_on_detail_page(): void
    {
        $dep = Department::factory()->create();
        $operator = User::factory()->operator()->withDepartment($dep->id)->create();
        $signer = User::factory()->signer()->withDepartment($dep->id)->create();

        $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n190\n%%EOF";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent("surat.pdf", $pdfContent);

        $response = $this->actingAs($operator)->post(route("documents.store"), [
            "title" => "Surat Edaran Penting",
            "doc_number" => "EDARAN/2026/001",
            "doc_date" => "2026-10-14",
            "doc_type" => "Surat Edaran",
            "unit" => "Sekretariat",
            "classification" => "biasa",
            "summary" => "Ini adalah ringkasan dokumen tentang kebijakan baru 2026.",
            "notes" => "Catatan pelengkap",
            "signers" => [$signer->id],
            "file" => $file,
        ]);

        $response->assertRedirect();

        $doc = Document::where("doc_number", "EDARAN/2026/001")->first();
        $this->assertNotNull($doc);
        $this->assertEquals("Ini adalah ringkasan dokumen tentang kebijakan baru 2026.", $doc->summary);

        // View detail page
        $detailResponse = $this->actingAs($operator)->get(route("documents.show", $doc));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee("Ringkasan Dokumen");
        $detailResponse->assertSee("Ini adalah ringkasan dokumen tentang kebijakan baru 2026.");
    }

    /**
     * Test document summary is shown on public verification page for regular docs.
     */
    public function test_document_summary_is_shown_on_verification_page_for_public(): void
    {
        $dep = Department::factory()->create();
        $owner = User::factory()->operator()->withDepartment($dep->id)->create();

        $doc = $this->createDocWithFiles([
            "creator_id" => $owner->id,
            "department_id" => $dep->id,
            "status" => "signed_valid",
            "classification" => "biasa",
            "summary" => "Ringkasan dokumen yang dapat dilihat publik.",
        ]);

        $response = $this->get(route("verify", $doc->qr_token));
        $response->assertStatus(200);
        $response->assertSee("Ringkasan Dokumen");
        $response->assertSee("Ringkasan dokumen yang dapat dilihat publik.");
    }

    /**
     * Test document summary is masked on verification page when confidential.
     */
    public function test_document_summary_is_masked_on_verification_page_when_confidential(): void
    {
        $dep = Department::factory()->create();
        $owner = User::factory()->operator()->withDepartment($dep->id)->create();

        $doc = $this->createDocWithFiles([
            "creator_id" => $owner->id,
            "department_id" => $dep->id,
            "status" => "signed_valid",
            "classification" => "rahasia",
            "summary" => "Informasi rahasia internal tingkat tinggi.",
        ]);

        // Guest accesses verification page
        $response = $this->get(route("verify", $doc->qr_token));
        $response->assertStatus(200);
        $response->assertDontSee("Informasi rahasia internal tingkat tinggi.");
        $response->assertSee("*** (Dirahasiakan)");
    }
}
