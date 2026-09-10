<?php

namespace Tests\Feature\Verification;

use App\Models\Document;
use App\Models\User;
use App\Models\VerificationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // =========================================================================
    // Public Verification Tests (No Auth Required)
    // =========================================================================

    /** @test */
    public function public_can_access_verification_page_with_valid_token(): void
    {
        $doc = Document::factory()->signedValid()->create();
        $verification = VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'valid',
        ]);

        $this->get(route('verify', $doc->qr_token))
            ->assertStatus(200);
    }

    /** @test */
    public function verification_shows_not_found_for_invalid_token(): void
    {
        $this->get(route('verify', 'invalid-token-xyz'))
            ->assertStatus(200); // Menampilkan halaman not-found, bukan 404
    }

    /** @test */
    public function verification_page_is_accessible_without_login(): void
    {
        $doc = Document::factory()->signedValid()->create();
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'valid',
        ]);

        // Ensure no user is logged in
        $this->assertGuest();

        $this->get(route('verify', $doc->qr_token))
            ->assertStatus(200);
    }

    /** @test */
    public function revoked_document_shows_invalid_status(): void
    {
        $doc = Document::factory()->revoked()->create();
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'revoked',
        ]);

        $response = $this->get(route('verify', $doc->qr_token));
        $response->assertStatus(200);
        // The response should contain revoked status info
        $response->assertSee($doc->title);
    }

    /** @test */
    public function valid_document_preview_is_accessible_via_token(): void
    {
        // Create a fake signed PDF file
        $signedFilePath = 'documents/signed_test.pdf';
        Storage::disk('local')->put($signedFilePath, "%PDF-1.4 fake content");

        $doc = Document::factory()->signedValid()->create([
            'signed_file_path' => $signedFilePath,
        ]);
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'valid',
        ]);

        $this->get(route('verify.preview', $doc->qr_token))
            ->assertStatus(200);
    }

    /** @test */
    public function invalid_token_preview_returns_404(): void
    {
        $this->get(route('verify.preview', 'invalid-token'))
            ->assertStatus(404);
    }

    /** @test */
    public function revoked_document_preview_is_accessible_when_file_exists(): void
    {
        $signedFilePath = 'documents/signed_revoked_test.pdf';
        Storage::disk('local')->put($signedFilePath, "%PDF-1.4 fake content for revoked test");

        $doc = Document::factory()->revoked()->create([
            'signed_file_path' => $signedFilePath,
        ]);
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'revoked',
        ]);
        $this->get(route('verify.preview', $doc->qr_token))
            ->assertStatus(200);
    }

    /** @test */
    public function verification_page_shows_preview_button_and_hides_download_button(): void
    {
        $doc = Document::factory()->signedValid()->create();
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'valid',
        ]);

        $response = $this->get(route('verify', $doc->qr_token));

        $response->assertStatus(200);
        $response->assertSee('Lihat File');
        $response->assertDontSee('Unduh PDF');
    }

    /** @test */
    public function download_route_redirects_to_preview_for_all_roles(): void
    {
        $doc = Document::factory()->signedValid()->create();

        // Test as guest
        $this->get(route('verify.download', $doc->qr_token))
            ->assertRedirect(route('verify.preview', $doc->qr_token));

        // Test as authenticated signer
        $signer = User::factory()->signer()->create();
        $this->actingAs($signer)
            ->get(route('verify.download', $doc->qr_token))
            ->assertRedirect(route('verify.preview', $doc->qr_token));

        // Test as authenticated admin
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('verify.download', $doc->qr_token))
            ->assertRedirect(route('verify.preview', $doc->qr_token));
    }
}
