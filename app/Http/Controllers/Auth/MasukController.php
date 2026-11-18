<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasukController extends Controller
{
    public function create(): View
    {
        return view('auth.masuk');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $kunci = Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.',
            ]);
        }

        if (! Auth::attempt([...$data, 'aktif' => true], $request->boolean('ingat'))) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('katalog');
    }
}
