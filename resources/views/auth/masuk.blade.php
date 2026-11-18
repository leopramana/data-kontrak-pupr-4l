@extends('layouts.app')
@section('judul', 'Masuk')

@section('konten')
    @php($sembunyikanGalatUmum = true)
    <div class="mx-auto max-w-sm">
        <h1 class="text-2xl font-semibold">Masuk ke panel</h1>
        <p class="mt-1 text-sm text-slate-600">Untuk operator OPD, walidata, dan admin. Akun dibuat oleh admin Aptika.</p>

        <form method="POST" action="{{ route('masuk') }}" class="kartu mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="input" @error('email') aria-invalid="true" aria-describedby="email-galat" @enderror>
                @error('email')<p id="email-galat" class="galat">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="label">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="ingat" value="1" class="rounded border-slate-300"> Ingat saya
            </label>
            <button class="btn btn-utama w-full">Masuk</button>
        </form>
    </div>
@endsection
