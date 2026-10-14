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
        $signedFilePath = 'documents/signed_test.pdf';
        $content = "%PDF-1.4 valid test content";
        Storage::disk('local')->put($signedFilePath, $content);

        $doc = Document::factory()->signedValid()->create([
            'signed_file_path' => $signedFilePath,
            'hash' => hash('sha256', $content),
        ]);
        VerificationRecord::factory()->create([
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
            ->assertStatus(200); // Menampilkan halaman not-found
    }

    /** @test */
    public function verification_page_is_accessible_without_login(): void
    {
        $signedFilePath = 'documents/signed_test.pdf';
        $content = "%PDF-1.4 valid test content";
        Storage::disk('local')->put($signedFilePath, $content);

        $doc = Document::factory()->signedValid()->create([
            'signed_file_path' => $signedFilePath,
            'hash' => hash('sha256', $content),
        ]);
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
    public function revoked_document_shows_revoked_status(): void
    {
        $doc = Document::factory()->revoked()->create();
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'revoked',
        ]);

        $response = $this->get(route('verify', $doc->qr_token));
        $response->assertStatus(200);
        $response->assertSee('DICABUT');
    }

    /** @test */
    public function public_guest_cannot_preview_document_without_login(): void
    {
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

        // Guest is redirected to login (SEC-03)
        $this->get(route('verify.preview', $doc->qr_token))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function authorized_user_can_preview_via_token(): void
    {
        $user = User::factory()->superAdmin()->create();
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

        $this->actingAs($user)
            ->get(route('verify.preview', $doc->qr_token))
            ->assertStatus(200);
    }

    /** @test */
    public function invalid_token_preview_returns_404(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)
            ->get(route('verify.preview', 'invalid-token'))
            ->assertStatus(404);
    }

    /** @test */
    public function unauthorized_user_cannot_preview_via_token(): void
    {
        $otherUser = User::factory()->operator()->create();
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

        $this->actingAs($otherUser)
            ->get(route('verify.preview', $doc->qr_token))
            ->assertForbidden();
    }

    /** @test */
    public function verification_page_hides_preview_from_guest(): void
    {
        $signedFilePath = 'documents/signed_test.pdf';
        $content = "%PDF-1.4 test";
        Storage::disk('local')->put($signedFilePath, $content);

        $doc = Document::factory()->signedValid()->create([
            'signed_file_path' => $signedFilePath,
            'hash' => hash('sha256', $content),
        ]);
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'valid',
        ]);

        $response = $this->get(route('verify', $doc->qr_token));

        $response->assertStatus(200);
        $response->assertDontSee('Lihat File');
        $response->assertSee('Akses isi file PDF memerlukan akun yang berwenang');
    }

    /** @test */
    public function download_route_redirects_guest_to_login(): void
    {
        $doc = Document::factory()->signedValid()->create();
        VerificationRecord::factory()->create([
            'document_id' => $doc->id,
            'token' => $doc->qr_token,
            'status' => 'valid',
        ]);

        $this->get(route('verify.download', $doc->qr_token))
            ->assertRedirect(route('login'));
    }
}
