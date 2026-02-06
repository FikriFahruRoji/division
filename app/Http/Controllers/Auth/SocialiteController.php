<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Use stateless to avoid InvalidStateException (session state mismatch)
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Gagal login dengan Google. Silakan coba lagi.');
        }

        // Find existing user by email
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Email tidak terdaftar. Hubungi administrator untuk membuat akun.');
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        // Login the user
        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
