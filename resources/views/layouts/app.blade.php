<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('judul')@yield('judul') · @endif{{ config('app.name') }} {{ config('katalog.pemda') }}</title>
    <meta name="description" content="Katalog data resmi Organisasi Perangkat Daerah {{ config('katalog.pemda') }}.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-slate-50 text-slate-800 antialiased">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">Lewati ke konten</a>

    @php($logo = collect(['png', 'svg', 'jpg', 'jpeg', 'webp'])->map(fn ($e) => "images/logo.$e")->first(fn ($p) => file_exists(public_path($p))))
    <header class="border-b border-brand-200 bg-brand-100 text-brand-900">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('katalog') }}" class="flex items-center gap-3">
                @if($logo)
                    <img src="{{ asset($logo) }}" alt="Logo Cicak Studio" class="h-10 w-auto">
                @else
                    <span class="grid size-9 place-items-center rounded-lg bg-brand-600 text-lg font-semibold text-white" aria-hidden="true">KD</span>
                @endif
                <span class="leading-tight">
                    <span class="block font-semibold">Katalog Data OPD</span>
                    <span class="block text-xs text-brand-800">{{ config('katalog.pemda') }}</span>
                </span>
            </a>

            <nav class="flex items-center gap-1 text-sm" aria-label="Navigasi utama">
                <a href="{{ route('katalog') }}" class="rounded-md px-3 py-2 hover:bg-brand-200 @if(request()->routeIs('katalog*')) bg-brand-200 font-medium @endif">Katalog</a>
                <a href="{{ route('tentang') }}" class="rounded-md px-3 py-2 hover:bg-brand-200 @if(request()->routeIs('tentang')) bg-brand-200 font-medium @endif">Tentang</a>
                @auth
                    <a href="{{ route('panel') }}" class="rounded-md px-3 py-2 hover:bg-brand-200 @if(request()->is('panel*')) bg-brand-200 font-medium @endif">Panel</a>
                    <form method="POST" action="{{ route('keluar') }}">
                        @csrf
                        <button class="rounded-md px-3 py-2 hover:bg-brand-200">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('masuk') }}" class="rounded-md bg-brand-700 px-3 py-2 font-medium text-white hover:bg-brand-800">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    @auth
        @if(request()->is('panel*'))
            @include('partials.nav-panel')
        @endif
    @endauth

    <main id="konten" class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
        @include('partials.flash')
        @yield('konten')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500">
            © {{ now()->year }} {{ config('katalog.pengelola') }} · {{ config('katalog.pemda') }}.
            Data yang tampil sudah diperiksa walidata dan tidak memuat data pribadi warga.
        </div>
    </footer>
</body>
</html>
