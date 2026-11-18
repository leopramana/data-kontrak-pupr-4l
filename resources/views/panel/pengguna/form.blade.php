@extends('layouts.app')
@php($baru = ! $pengguna->exists)
@section('judul', $baru ? 'Tambah akun' : 'Ubah akun')

@section('konten')
    @php($sembunyikanGalatUmum = true)
    <div class="max-w-xl">
        <a href="{{ route('pengguna.index') }}" class="text-sm text-slate-500 hover:underline">← Pengguna</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $baru ? 'Tambah akun' : 'Ubah akun '.$pengguna->email }}</h1>

        <form method="POST" action="{{ $baru ? route('pengguna.store') : route('pengguna.update', $pengguna) }}" class="kartu mt-6 space-y-5">
            @csrf
            @unless($baru) @method('PUT') @endunless

            <div>
                <label for="name" class="label">Nama</label>
                <input id="name" name="name" value="{{ old('name', $pengguna->name) }}" required maxlength="100" class="input">
                @error('name')<p class="galat">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $pengguna->email) }}" required class="input">
                @error('email')<p class="galat">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="role" class="label">Peran</label>
                    <select id="role" name="role" required class="input">
                        @foreach(\App\Enums\Peran::cases() as $r)
                            <option value="{{ $r->value }}" @selected(old('role', $pengguna->role?->value ?? 'operator') === $r->value)>{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="galat">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="opd_id" class="label">OPD</label>
                    <select id="opd_id" name="opd_id" class="input">
                        <option value="">–</option>
                        @foreach($daftarOpd as $opd)
                            <option value="{{ $opd->id }}" @selected((string) old('opd_id', $pengguna->opd_id) === (string) $opd->id)>{{ $opd->nama }}</option>
                        @endforeach
                    </select>
                    <p class="bantuan">Wajib untuk operator. Diabaikan untuk peran lain.</p>
                    @error('opd_id')<p class="galat">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="label">Kata sandi {{ $baru ? '' : 'baru' }}</label>
                    <input id="password" name="password" type="password" @if($baru) required @endif minlength="8" autocomplete="new-password" class="input">
                    <p class="bantuan">{{ $baru ? 'Minimal 8 karakter.' : 'Kosongkan bila tidak diganti.' }}</p>
                    @error('password')<p class="galat">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Ulangi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $pengguna->aktif)) class="rounded border-slate-300">
                Akun aktif
            </label>

            <div class="flex gap-2 border-t border-slate-100 pt-5">
                <button class="btn btn-utama">Simpan</button>
                <a href="{{ route('pengguna.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </div>
@endsection
