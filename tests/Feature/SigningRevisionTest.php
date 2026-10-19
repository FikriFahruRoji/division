<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\Signature;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Services\DocumentService;
use App\Services\SigningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use TCPDF;
use Tests\TestCase;

/**
 * T4: signing rounds are kept as history instead of being deleted on re-submission.
 */
class SigningRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private User $signer;
    private SigningService $signing;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['signing.seal.key_bits' => 2048]);

        $dept = Department::factory()->create();
        $this->operator = User::factory()->operator()->withDepartment($dept->id)->create();
        $this->signer = User::factory()->signer()->withDepartment($dept->id)->withTwoFactor()->create();
        $this->signing = app(SigningService::class);
        $this->actingAs($this->operator);
    }

    private function pdf(string $text): string
    {
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->Write(0, $text);

        return $pdf->Output('', 'S');
    }

    /** A document in pending_signature for $this->signer, round 1. */
    private function submittedDocument(): Document
    {
        $doc = Document::factory()->draft()->withCreator($this->operator)->create(['department_id' => $this->operator->department_id]);
        $content = $this->pdf('Versi pertama');
        Storage::disk('local')->put($doc->file_path, $content);
        $doc->update(['hash' => hash('sha256', $content)]);
        VerificationRecord::factory()->create(['document_id' => $doc->id, 'token' => $doc->qr_token]);

        $this->signing->assignSigners($doc, [$this->signer->id]);
        $this->signing->finalizeDocument($doc->fresh());

        return $doc->fresh();
    }

    private function rejectedDocument(): Document
    {
        $doc = $this->submittedDocument();
        $this->signing->rejectDocument($doc, $this->signer, 'Nomor surat salah', 'totp');

        return $doc->fresh();
    }

    public function test_resubmitting_a_rejected_document_keeps_the_rejected_round(): void
    {
        $doc = $this->rejectedDocument();
        $rejected = $doc->signerAssignments()->first();

        $this->signing->finalizeDocument($doc);
        $doc->refresh();

        $this->assertSame(2, $doc->revision);
        $this->assertSame('pending_signature', $doc->status);

        $old = SignerAssignment::find($rejected->id);
        $this->assertNotNull($old->superseded_at);
        $this->assertSame('rejected', $old->status);
        $this->assertSame('Nomor surat salah', $old->rejection_reason);
        $this->assertSame(1, $old->revision);

        $current = $doc->signerAssignments()->get();
        $this->assertCount(1, $current);
        $this->assertSame(2, $current->first()->revision);
        $this->assertSame($this->signer->id, $current->first()->signer_id);
        $this->assertContains($current->first()->status, ['pending', 'notified']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'round_superseded', 'document_id' => $doc->id]);
    }

    public function test_signatures_of_a_closed_round_are_superseded_not_deleted(): void
    {
        $doc = $this->rejectedDocument();
        // A signature from an earlier signer in the same round (multi-signer case)
        $signature = Signature::create([
            'document_id' => $doc->id,
            'signer_id' => $this->signer->id,
            'signature_blob_path' => 'documents/signed_old.pdf',
            'signed_hash' => str_repeat('a', 64),
            'revision' => 1,
        ]);

        $this->signing->finalizeDocument($doc);

        $this->assertNotNull($signature->fresh()->superseded_at);
        $this->assertCount(0, $doc->fresh()->signatures);
        $this->assertCount(1, $doc->fresh()->signatureHistory);
    }

    public function test_reject_revise_and_sign_again_keeps_full_history(): void
    {
        $doc = $this->rejectedDocument();

        // Operator uploads a corrected file and keeps the same signer
        $newContent = $this->pdf('Versi perbaikan');
        app(DocumentService::class)->updateFile($doc, UploadedFile::fake()->createWithContent('perbaikan.pdf', $newContent));
        $this->signing->assignSigners($doc->fresh(), [$this->signer->id]);
        $this->signing->finalizeDocument($doc->fresh());

        $this->actingAs($this->signer);
        $signature = $this->signing->signDocument($doc->fresh(), $this->signer, [], 'totp');
        $doc->refresh();

        $this->assertSame('signed_valid', $doc->status);
        $this->assertSame(2, $signature->revision);
        $this->assertSame(2, $doc->allSignerAssignments()->count());
        $this->assertSame(
            ['rejected', 'signed'],
            $doc->allSignerAssignments()->reorder('revision')->pluck('status')->all()
        );
        $this->assertSame(1, $doc->allSignerAssignments()->whereNotNull('superseded_at')->count());
    }

    public function test_editing_a_rejected_document_closes_the_round_once(): void
    {
        $doc = $this->rejectedDocument();

        $this->signing->assignSigners($doc, [$this->signer->id]);
        $this->signing->assignSigners($doc->fresh(), [$this->signer->id]);
        $this->signing->finalizeDocument($doc->fresh());

        $this->assertSame(2, $doc->fresh()->revision);
        $this->assertSame(1, AuditLog::where('action', 'round_superseded')->count());
        $this->assertSame(1, SignerAssignment::where('document_id', $doc->id)->where('status', 'rejected')->count());
    }

    public function test_replacing_a_file_keeps_the_previous_file_and_records_both_hashes(): void
    {
        $doc = $this->rejectedDocument();
        $oldPath = $doc->file_path;
        $oldHash = $doc->hash;

        app(DocumentService::class)->updateFile($doc, UploadedFile::fake()->createWithContent('baru.pdf', $this->pdf('Baru')));

        Storage::disk('local')->assertExists($oldPath);
        $log = AuditLog::where('action', 'file_update')->latest('id')->first();
        $this->assertSame($oldPath, $log->metadata['previous_file_path']);
        $this->assertSame($oldHash, $log->metadata['previous_hash']);
        $this->assertSame($doc->fresh()->hash, $log->metadata['new_hash']);
    }

    public function test_document_with_signature_history_cannot_be_deleted(): void
    {
        $doc = $this->rejectedDocument();
        Signature::create([
            'document_id' => $doc->id,
            'signer_id' => $this->signer->id,
            'signature_blob_path' => 'documents/signed_old.pdf',
            'signed_hash' => str_repeat('b', 64),
            'superseded_at' => now(),
        ]);

        $this->delete(route('documents.destroy', $doc))->assertForbidden();
        $this->assertDatabaseHas('documents', ['id' => $doc->id]);
    }

    public function test_rejected_document_without_signatures_can_still_be_deleted(): void
    {
        $doc = $this->rejectedDocument();

        $this->delete(route('documents.destroy', $doc))->assertRedirect();
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    }

    public function test_detail_page_shows_past_rounds_and_allows_resubmission_after_edit(): void
    {
        $doc = $this->rejectedDocument();
        $this->travel(10)->seconds();
        $this->signing->assignSigners($doc, [$this->signer->id]);
        $doc->touch();

        $this->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('Riwayat Putaran')
            ->assertSee('Nomor surat salah')
            ->assertSee('Ajukan kembali dokumen');
    }

    public function test_signer_history_still_lists_documents_signed_in_a_superseded_round(): void
    {
        $doc = $this->submittedDocument();
        SignerAssignment::where('document_id', $doc->id)->update(['status' => 'signed', 'signed_at' => now(), 'superseded_at' => now()]);

        $this->actingAs($this->signer)
            ->get(route('signatures.history'))
            ->assertOk()
            ->assertSee($doc->title);
    }
}
