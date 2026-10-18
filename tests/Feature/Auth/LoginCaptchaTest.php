<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Scyllaly\HCaptcha\HCaptcha;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private function enableCaptcha(?bool $verifyResult = null): void
    {
        config([
            'HCaptcha.enabled' => true,
            'HCaptcha.sitekey' => 'test-sitekey',
            'HCaptcha.secret' => 'test-secret',
        ]);

        // Singleton dibangun dari config, jadi buang instance lama agar flag enabled terbaca ulang.
        $this->app->forgetInstance('HCaptcha');

        if ($verifyResult !== null) {
            $mock = $this->partialMock(HCaptcha::class, function ($mock) use ($verifyResult) {
                $mock->shouldReceive('verifyResponse')->andReturn($verifyResult);
            });
            $this->app->instance('HCaptcha', $mock);
        }
    }

    public function test_captcha_is_disabled_in_testing_by_default(): void
    {
        $this->assertFalse((bool) config('HCaptcha.enabled'));

        $this->withoutVite()->get('/login')
            ->assertOk()
            ->assertDontSee('h-captcha', false)
            ->assertDontSee('js.hcaptcha.com', false);
    }

    public function test_login_without_captcha_succeeds_when_disabled(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_login_screen_renders_widget_when_enabled(): void
    {
        $this->enableCaptcha();

        $this->withoutVite()->get('/login')
            ->assertOk()
            ->assertSee('data-sitekey="test-sitekey"', false)
            ->assertSee('js.hcaptcha.com', false);
    }

    public function test_login_without_captcha_token_is_rejected_when_enabled(): void
    {
        $this->enableCaptcha();
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'h-captcha-response' => 'Silakan verifikasi bahwa Anda bukan robot.',
        ]);

        $this->assertGuest();
    }

    public function test_login_with_invalid_captcha_token_is_rejected_when_enabled(): void
    {
        $this->enableCaptcha(verifyResult: false);
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'h-captcha-response' => 'bogus-token',
        ])->assertSessionHasErrors([
            'h-captcha-response' => 'Verifikasi CAPTCHA gagal. Silakan coba lagi.',
        ]);

        $this->assertGuest();
    }

    public function test_login_with_valid_captcha_token_succeeds_when_enabled(): void
    {
        $this->enableCaptcha(verifyResult: true);
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'h-captcha-response' => 'valid-token',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
