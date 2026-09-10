<?php

namespace Tests\Unit\Models;

use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Models\VerificationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Status Helper Tests
    // =========================================================================

    /** @test */
    public function it_identifies_draft_status(): void
    {
        $doc = Document::factory()->draft()->create();
        $this->assertTrue($doc->isDraft());
        $this->assertFalse($doc->isPendingSignature());
        $this->assertFalse($doc->isSignedValid());
    }

    /** @test */
    public function it_identifies_pending_signature_status(): void
    {
        $doc = Document::factory()->pendingSignature()->create();
        $this->assertFalse($doc->isDraft());
        $this->assertTrue($doc->isPendingSignature());
        $this->assertFalse($doc->isSignedValid());
    }

    /** @test */
    public function it_identifies_signed_valid_status(): void
    {
        $doc = Document::factory()->signedValid()->create();
        $this->assertFalse($doc->isDraft());
        $this->assertFalse($doc->isPendingSignature());
        $this->assertTrue($doc->isSignedValid());
    }

    /** @test */
    public function it_identifies_revoked_status(): void
    {
        $doc = Document::factory()->revoked()->create();
        $this->assertTrue($doc->isRevoked());
    }

    /** @test */
    public function it_identifies_superseded_status(): void
    {
        $doc = Document::factory()->superseded()->create();
        $this->assertTrue($doc->isSuperseded());
    }

    // =========================================================================
    // Auto-generate UUID and QR Token Tests
    // =========================================================================

    /** @test */
    public function it_auto_generates_uuid_on_creation(): void
    {
        $doc = Document::factory()->create();
        $this->assertNotNull($doc->uuid);
        $this->assertTrue(Str::isUuid($doc->uuid));
    }

    /** @test */
    public function it_auto_generates_qr_token_on_creation(): void
    {
        $doc = Document::factory()->create();
        $this->assertNotNull($doc->qr_token);
        $this->assertTrue(Str::isUuid($doc->qr_token));
    }

    // =========================================================================
    // Signer Logic Tests
    // =========================================================================

    /** @test */
    public function it_gets_next_pending_signer(): void
    {
        $doc = Document::factory()->pendingSignature()->create();
        $signer1 = User::factory()->signer()->create();
        $signer2 = User::factory()->signer()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer1->id,
            'order_index' => 1,
            'status' => 'notified',
        ]);
        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer2->id,
            'order_index' => 2,
            'status' => 'pending',
        ]);

        $next = $doc->getNextSigner();
        $this->assertNotNull($next);
        $this->assertEquals($signer1->id, $next->signer_id);
    }

    /** @test */
    public function it_returns_null_next_signer_when_all_signed(): void
    {
        $doc = Document::factory()->signedValid()->create();
        $signer = User::factory()->signer()->create();

        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'signed',
        ]);

        $next = $doc->getNextSigner();
        $this->assertNull($next);
    }

    /** @test */
    public function it_correctly_checks_all_signers_signed(): void
    {
        $doc = Document::factory()->pendingSignature()->create();
        $signer1 = User::factory()->signer()->create();
        $signer2 = User::factory()->signer()->create();

        $assignment1 = SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer1->id,
            'order_index' => 1,
            'status' => 'signed',
        ]);
        $assignment2 = SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer2->id,
            'order_index' => 2,
            'status' => 'pending',
        ]);

        $this->assertFalse($doc->allSignersSigned());

        $assignment2->update(['status' => 'signed']);

        $this->assertTrue($doc->fresh()->allSignersSigned());
    }

    /** @test */
    public function it_returns_false_for_all_signers_signed_with_no_assignments(): void
    {
        $doc = Document::factory()->create();
        $this->assertFalse($doc->allSignersSigned());
    }

    // =========================================================================
    // Route Key Tests
    // =========================================================================

    /** @test */
    public function it_uses_uuid_as_route_key(): void
    {
        $doc = Document::factory()->create();
        $this->assertEquals('uuid', $doc->getRouteKeyName());
    }

    // =========================================================================
    // Verification URL Tests
    // =========================================================================

    /** @test */
    public function it_generates_verification_url(): void
    {
        $doc = Document::factory()->create();
        $url = $doc->getVerificationUrl();
        $this->assertStringContainsString($doc->qr_token, $url);
    }
}
