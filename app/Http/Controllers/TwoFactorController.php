<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\Request;

/**
 * T1: self-service TOTP enrollment. The second factor is required to sign or reject documents.
 */
class TwoFactorController extends Controller
{
    private const PENDING_KEY = 'two_factor_pending_secret';

    public function __construct(protected TwoFactorService $twoFactor) {}

    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('profile.two-factor', ['user' => $user]);
        }

        $secret = $request->session()->get(self::PENDING_KEY);
        if (!$secret) {
            $secret = $this->twoFactor->generateSecret();
            $request->session()->put(self::PENDING_KEY, $secret);
        }

        return view('profile.two-factor', [
            'user' => $user,
            'secret' => $secret,
            'qrCode' => $this->twoFactor->qrCodeDataUri($user, $secret),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['code' => 'required|string|max:10']);

        $user = $request->user();
        $secret = $request->session()->get(self::PENDING_KEY);

        if ($user->hasTwoFactorEnabled() || !$secret) {
            return redirect()->route('two-factor.show');
        }

        $this->twoFactor->enable($user, $secret, $request->input('code'));
        $request->session()->forget(self::PENDING_KEY);

        return redirect()
            ->to($request->session()->pull('two_factor_intended', route('two-factor.show')))
            ->with('success', 'Autentikasi dua faktor aktif. Kode dari aplikasi authenticator kini diperlukan saat menandatangani.');
    }

    public function destroy(Request $request)
    {
        $request->validate(['code' => 'required|string|max:10']);

        $user = $request->user();
        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.show');
        }

        $this->twoFactor->verify($user, $request->input('code'), 'code');
        $this->twoFactor->disable($user);

        return redirect()
            ->route('two-factor.show')
            ->with('success', 'Autentikasi dua faktor dinonaktifkan. Anda tidak dapat menandatangani dokumen sampai mengaktifkannya kembali.');
    }
}
