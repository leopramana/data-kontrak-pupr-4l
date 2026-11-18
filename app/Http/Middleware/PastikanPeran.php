<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan akun masih aktif dan (bila disebut) memiliki salah satu peran.
 * Contoh: ->middleware('peran:walidata') atau ->middleware('peran') untuk sekadar cek aktif.
 */
class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('masuk'));
        }

        if (! $user->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('masuk')->withErrors(['email' => 'Akun Anda sudah dinonaktifkan. Hubungi admin.']);
        }

        if ($peran !== [] && ! in_array($user->role->value, $peran, true)) {
            abort(403, 'Halaman ini tidak tersedia untuk peran Anda.');
        }

        return $next($request);
    }
}
