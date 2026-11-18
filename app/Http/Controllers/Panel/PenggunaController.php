<?php

namespace App\Http\Controllers\Panel;

use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Pengelolaan akun oleh admin (T1.5). */
class PenggunaController extends Controller
{
    public function index(): View
    {
        return view('panel.pengguna.index', [
            'pengguna' => User::with('opd')->orderBy('role')->orderBy('name')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('panel.pengguna.form', ['pengguna' => new User(['aktif' => true]), 'daftarOpd' => Opd::orderBy('nama')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, null);

        User::create($data);

        return redirect()->route('pengguna.index')->with('sukses', "Akun {$data['email']} dibuat.");
    }

    public function edit(User $pengguna): View
    {
        return view('panel.pengguna.form', ['pengguna' => $pengguna, 'daftarOpd' => Opd::orderBy('nama')->get()]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $data = $this->validasi($request, $pengguna);

        if ($pengguna->is($request->user()) && (! $data['aktif'] || $data['role'] !== Peran::Admin->value)) {
            return back()->withInput()->withErrors(['role' => 'Anda tidak bisa menonaktifkan atau menurunkan peran akun Anda sendiri.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $pengguna->update($data);

        return redirect()->route('pengguna.index')->with('sukses', "Akun {$pengguna->email} diperbarui.");
    }

    private function validasi(Request $request, ?User $pengguna): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($pengguna?->id)],
            'role' => ['required', Rule::enum(Peran::class)],
            'opd_id' => ['nullable', 'required_if:role,operator', 'exists:opd,id'],
            'password' => [$pengguna ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], [
            'opd_id.required_if' => 'Operator wajib dikaitkan dengan satu OPD.',
        ]);

        $data['aktif'] = $request->boolean('aktif');

        if ($data['role'] !== Peran::Operator->value) {
            $data['opd_id'] = null;
        }

        return $data;
    }
}
