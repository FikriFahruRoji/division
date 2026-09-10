<?php

namespace Tests\Feature\Document;

use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // =========================================================================
    // Access Control Tests
    // =========================================================================

    /** @test */
    public function guest_cannot_access_documents_index(): void
    {
        $this->get(route('documents.index'))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function signer_cannot_access_document_create_form(): void
    {
        $signer = User::factory()->signer()->create();
        $this->actingAs($signer)
            ->get(route('documents.create'))
            ->assertStatus(403);
    }

    /** @test */
    public function operator_can_access_document_create_form(): void
    {
        $dept = Department::factory()->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();
        $operator = User::factory()->operator()->withDepartment($dept->id)->create();

        $this->actingAs($operator)
            ->get(route('documents.create'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_document_create_form(): void
    {
        $dept = Department::factory()->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();
        $admin = User::factory()->admin()->withDepartment($dept->id)->create();

        $this->actingAs($admin)
            ->get(route('documents.create'))
            ->assertStatus(200);
    }

    // =========================================================================
    // Document Store Tests
    // =========================================================================

    /** @test */
    public function operator_can_upload_document_with_valid_data(): void
    {
        $dept = Department::factory()->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();
        $operator = User::factory()->operator()->withDepartment($dept->id)->create();

        // Create a real PDF file for testing
        $pdfContent = $this->createFakePdfContent();
        $file = UploadedFile::fake()->createWithContent('test.pdf', $pdfContent);

        $response = $this->actingAs($operator)->post(route('documents.store'), [
            'title' => 'Surat Keputusan Test',
            'doc_number' => 'SK/001/2024',
            'doc_date' => '2024-01-15',
            'doc_type' => 'Surat Keputusan',
            'unit' => 'Divisi IT',
            'classification' => 'biasa',
            'sign_mode' => 'single',
            'signers' => [$signer->id],
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'title' => 'Surat Keputusan Test',
            'doc_number' => 'SK/001/2024',
            'creator_id' => $operator->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function signer_cannot_upload_document(): void
    {
        $dept = Department::factory()->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();

        $pdfContent = $this->createFakePdfContent();
        $file = UploadedFile::fake()->createWithContent('test.pdf', $pdfContent);

        $this->actingAs($signer)->post(route('documents.store'), [
            'title' => 'Test Document',
            'doc_number' => 'DOC/001/2024',
            'doc_date' => '2024-01-15',
            'doc_type' => 'Surat Tugas',
            'unit' => 'Unit Test',
            'classification' => 'biasa',
            'sign_mode' => 'single',
            'signers' => [$signer->id],
            'file' => $file,
        ])->assertStatus(403);
    }

    /** @test */
    public function document_upload_requires_valid_fields(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)->post(route('documents.store'), [])
            ->assertSessionHasErrors(['title', 'doc_number', 'doc_date', 'doc_type', 'unit', 'file', 'signers']);
    }

    /** @test */
    public function non_pdf_file_is_rejected(): void
    {
        $dept = Department::factory()->create();
        $signer = User::factory()->signer()->withDepartment($dept->id)->create();
        $operator = User::factory()->operator()->withDepartment($dept->id)->create();

        // Create a non-PDF file
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->actingAs($operator)->post(route('documents.store'), [
            'title' => 'Test Document',
            'doc_number' => 'DOC/002/2024',
            'doc_date' => '2024-01-15',
            'doc_type' => 'Surat Tugas',
            'unit' => 'Unit Test',
            'classification' => 'biasa',
            'sign_mode' => 'single',
            'signers' => [$signer->id],
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    // =========================================================================
    // Document Show Tests
    // =========================================================================

    /** @test */
    public function creator_can_view_own_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->withCreator($creator)->create();

        $this->actingAs($creator)
            ->get(route('documents.show', $doc))
            ->assertStatus(200);
    }

    /** @test */
    public function unrelated_user_cannot_view_document(): void
    {
        $creator = User::factory()->operator()->create();
        $otherUser = User::factory()->signer()->create();
        $doc = Document::factory()->withCreator($creator)->create();

        $this->actingAs($otherUser)
            ->get(route('documents.show', $doc))
            ->assertStatus(403);
    }

    // =========================================================================
    // Document Delete Tests
    // =========================================================================

    /** @test */
    public function creator_can_delete_draft_document(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->draft()->withCreator($creator)->create();

        $this->actingAs($creator)
            ->delete(route('documents.destroy', $doc))
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    }

    /** @test */
    public function signed_document_cannot_be_deleted(): void
    {
        $creator = User::factory()->operator()->create();
        $doc = Document::factory()->signedValid()->withCreator($creator)->create();

        $this->actingAs($creator)
            ->delete(route('documents.destroy', $doc))
            ->assertStatus(403);
    }

    /**
     * Create a minimal valid PDF content for testing.
     */
    protected function createFakePdfContent(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n190\n%%EOF";
    }
}
