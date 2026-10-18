<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PragmaRX\Google2FA\Google2FA;

abstract class TestCase extends BaseTestCase
{
    /**
     * Current TOTP code for a user with a confirmed second factor.
     */
    protected function totp(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp($user->fresh()->two_factor_secret);
    }
}
