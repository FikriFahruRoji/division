<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * T1: TOTP second factor (RFC 6238) used to confirm signing actions.
 */
class TwoFactorService
{
    /** Accept codes from one 30-second step before/after now to tolerate clock drift. */
    private const WINDOW = 1;

    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 300;

    public function __construct(protected Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * QR code (data URI) for scanning the secret into an authenticator app.
     */
    public function qrCodeDataUri(User $user, string $secret): string
    {
        $otpauth = $this->google2fa->getQRCodeUrl(config('app.name', 'TTD Digital'), $user->email, $secret);

        $qr = new QrCode(
            data: $otpauth,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 220,
            margin: 8,
        );

        return (new PngWriter())->write($qr)->getDataUri();
    }

    /**
     * Confirm a pending secret and enable 2FA for the user.
     */
    public function enable(User $user, string $secret, string $code, string $field = 'code'): void
    {
        $this->throttle($user, $field);

        $step = $this->google2fa->verifyKeyNewer($secret, $this->normalize($code), 0, self::WINDOW);
        if ($step === false) {
            $this->fail($user, $field);
        }

        RateLimiter::clear($this->throttleKey($user));

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step' => $step,
        ])->save();

        AuditLog::log('two_factor_enabled', "Autentikasi dua faktor diaktifkan untuk {$user->name}", $user->id);
    }

    /**
     * Verify a code for an action, rejecting replays of an already used code.
     * Throws a ValidationException on the given field when the code is wrong.
     */
    public function verify(User $user, ?string $code, string $field = 'otp'): void
    {
        $this->throttle($user, $field);

        // Lock the user row so two concurrent requests cannot both spend the same code
        $step = DB::transaction(function () use ($user, $code) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();

            $step = $this->google2fa->verifyKeyNewer(
                $locked->two_factor_secret,
                $this->normalize((string) $code),
                $locked->two_factor_last_step ?? 0,
                self::WINDOW
            );

            if ($step !== false) {
                $locked->forceFill(['two_factor_last_step' => $step])->save();
            }

            return $step;
        });

        // Record failures outside the transaction so the audit entry is not rolled back
        if ($step === false) {
            $this->fail($user, $field);
        }

        $user->two_factor_last_step = $step;
        RateLimiter::clear($this->throttleKey($user));
    }

    public function disable(User $user, ?User $actor = null): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();

        $actor ??= $user;
        $description = $actor->is($user)
            ? "Autentikasi dua faktor dinonaktifkan oleh {$user->name}"
            : "Autentikasi dua faktor milik {$user->name} direset oleh {$actor->name}";

        AuditLog::log('two_factor_disabled', $description, $actor->id, null, ['target_user_id' => $user->id]);
    }

    protected function normalize(string $code): string
    {
        return preg_replace('/\s+/', '', $code);
    }

    protected function throttle(User $user, string $field = 'otp'): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($user), self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($user));
            throw ValidationException::withMessages([
                $field => "Terlalu banyak kode salah. Coba lagi dalam {$seconds} detik.",
            ]);
        }
    }

    protected function fail(User $user, string $field = 'otp'): never
    {
        RateLimiter::hit($this->throttleKey($user), self::DECAY_SECONDS);

        AuditLog::log('two_factor_failed', "Kode autentikasi salah untuk {$user->name}", $user->id);

        throw ValidationException::withMessages([
            $field => 'Kode autentikasi tidak valid atau sudah pernah dipakai.',
        ]);
    }

    protected function throttleKey(User $user): string
    {
        return 'totp:' . $user->id;
    }
}
