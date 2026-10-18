<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use TCPDF;
use Tests\TestCase;

/**
 * T1: signing and rejecting require a fresh TOTP code.
 */
class TwoFactorSigningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['signing.seal.key_bits' => 2048]);
    }

    private function pendingDocumentFor(User $signer): Document
    {
        $doc = Document::factory()->create(['status' => 'pending_signature', 'department_id' => $signer->department_id]);

        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->Write(0, 'Dokumen uji 2FA');
        $content = $pdf->Output('', 'S');
        Storage::disk('local')->put($doc->file_path, $content);
        $doc->update(['hash' => hash('sha256', $content), 'signed_file_path' => null]);

        VerificationRecord::factory()->create(['document_id' => $doc->id, 'token' => $doc->qr_token]);
        SignerAssignment::factory()->create([
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'order_index' => 1,
            'status' => 'pending',
        ]);

        return $doc;
    }

    private function signer(bool $withTwoFactor = true): User
    {
        $dept = Department::factory()->create();
        $factory = User::factory()->signer()->withDepartment($dept->id);

        return ($withTwoFactor ? $factory->withTwoFactor() : $factory)->create();
    }

    // ---------------------------------------------------------------------
    // Signing gate
    // ---------------------------------------------------------------------

    public function test_signer_without_two_factor_is_sent_to_enrollment(): void
    {
        $signer = $this->signer(false);
        $doc = $this->pendingDocumentFor($signer);

        $this->actingAs($signer)
            ->post(route('signatures.approve', $doc), ['otp' => '123456'])
            ->assertRedirect(route('two-factor.show'));

        $this->assertDatabaseCount('signatures', 0);
        $this->assertSame(route('signatures.show', $doc), session('two_factor_intended'));
    }

    public function test_signing_without_code_is_rejected(): void
    {
        $signer = $this->signer();
        $doc = $this->pendingDocumentFor($signer);

        $this->actingAs($signer)
            ->post(route('signatures.approve', $doc))
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseCount('signatures', 0);
    }

    public function test_signing_with_wrong_code_is_rejected_and_logged(): void
    {
        $signer = $this->signer();
        $doc = $this->pendingDocumentFor($signer);
        $wrong = $this->totp($signer) === '000000' ? '111111' : '000000';

        $this->actingAs($signer)
            ->post(route('signatures.approve', $doc), ['otp' => $wrong])
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseCount('signatures', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'two_factor_failed', 'user_id' => $signer->id]);
    }

    public function test_signing_with_valid_code_signs_and_records_auth_method(): void
    {
        $signer = $this->signer();
        $doc = $this->pendingDocumentFor($signer);

        $this->actingAs($signer)
            ->post(route('signatures.approve', $doc), ['otp' => $this->totp($signer)])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('signatures', [
            'document_id' => $doc->id,
            'signer_id' => $signer->id,
            'auth_method' => 'totp',
        ]);
        $this->assertSame('signed_valid', $doc->fresh()->status);
    }

    public function test_rejecting_requires_a_valid_code(): void
    {
        $signer = $this->signer();
        $doc = $this->pendingDocumentFor($signer);

        $this->actingAs($signer)
            ->post(route('signatures.reject', $doc), ['reason' => 'Perlu revisi'])
            ->assertSessionHasErrors('otp');

        $this->assertSame('pending_signature', $doc->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // Code handling
    // ---------------------------------------------------------------------

    public function test_a_code_cannot_be_used_twice(): void
    {
        $signer = $this->signer();
        $code = $this->totp($signer);
        $service = app(TwoFactorService::class);

        $service->verify($signer, $code);

        $this->expectException(ValidationException::class);
        $service->verify($signer->fresh(), $code);
    }

    public function test_too_many_wrong_codes_are_throttled(): void
    {
        $signer = $this->signer();
        $service = app(TwoFactorService::class);
        $wrong = $this->totp($signer) === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            try {
                $service->verify($signer, $wrong);
            } catch (ValidationException) {
            }
        }

        try {
            $service->verify($signer, $this->totp($signer));
            $this->fail('Correct code should be refused while throttled.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Terlalu banyak', $e->errors()['otp'][0]);
        }

        RateLimiter::clear('totp:' . $signer->id);
    }

    public function test_secret_is_encrypted_in_the_database(): void
    {
        $signer = $this->signer();

        $raw = \DB::table('users')->where('id', $signer->id)->value('two_factor_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', $raw);
        $this->assertSame('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', $signer->fresh()->two_factor_secret);
    }

    // ---------------------------------------------------------------------
    // Enrollment
    // ---------------------------------------------------------------------

    public function test_user_can_enroll_with_a_valid_code(): void
    {
        $signer = $this->signer(false);

        $this->actingAs($signer)->get(route('two-factor.show'))->assertOk()->assertSee('Kunci manual');
        $secret = session('two_factor_pending_secret');
        $this->assertNotEmpty($secret);

        $this->actingAs($signer)
            ->post(route('two-factor.store'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue($signer->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('audit_logs', ['action' => 'two_factor_enabled', 'user_id' => $signer->id]);
    }

    public function test_enrollment_with_wrong_code_does_not_enable(): void
    {
        $signer = $this->signer(false);
        $this->actingAs($signer)->get(route('two-factor.show'));
        $secret = session('two_factor_pending_secret');
        $wrong = app(Google2FA::class)->getCurrentOtp($secret) === '000000' ? '111111' : '000000';

        $this->actingAs($signer)
            ->post(route('two-factor.store'), ['code' => $wrong])
            ->assertSessionHasErrors('code');

        $this->assertFalse($signer->fresh()->hasTwoFactorEnabled());
    }

    public function test_user_can_disable_with_a_valid_code(): void
    {
        $signer = $this->signer();

        $this->actingAs($signer)
            ->delete(route('two-factor.destroy'), ['code' => $this->totp($signer)])
            ->assertSessionHasNoErrors();

        $this->assertFalse($signer->fresh()->hasTwoFactorEnabled());
    }

    // ---------------------------------------------------------------------
    // Admin reset
    // ---------------------------------------------------------------------

    public function test_admin_can_reset_two_factor_of_user_in_their_department(): void
    {
        $signer = $this->signer();
        $admin = User::factory()->admin()->withDepartment($signer->department_id)->create();

        $this->actingAs($admin)
            ->post(route('users.two-factor.reset', $signer))
            ->assertRedirect(route('users.show', $signer));

        $this->assertFalse($signer->fresh()->hasTwoFactorEnabled());
        $this->assertDatabaseHas('audit_logs', ['action' => 'two_factor_disabled', 'user_id' => $admin->id]);
    }

    public function test_admin_cannot_reset_two_factor_outside_their_department(): void
    {
        $signer = $this->signer();
        $otherDept = Department::factory()->create();
        $admin = User::factory()->admin()->withDepartment($otherDept->id)->create();

        $this->actingAs($admin)
            ->post(route('users.two-factor.reset', $signer))
            ->assertForbidden();

        $this->assertTrue($signer->fresh()->hasTwoFactorEnabled());
    }

    public function test_signer_cannot_reset_two_factor_of_others(): void
    {
        $target = $this->signer();
        $other = User::factory()->signer()->withDepartment($target->department_id)->create();

        $this->actingAs($other)
            ->post(route('users.two-factor.reset', $target))
            ->assertForbidden();
    }
}
