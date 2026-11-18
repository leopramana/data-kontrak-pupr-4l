@extends('layouts.app')
@section('judul', 'Katalog')

@section('konten')
    <section class="mb-8">
        <h1 class="text-2xl font-semibold sm:text-3xl">Katalog Data OPD</h1>
        <p class="mt-2 max-w-2xl text-slate-600">
            Temukan dan unduh data resmi dari perangkat daerah {{ config('katalog.pemda') }}.
            Setiap dataset sudah diperiksa walidata sebelum ditampilkan.
        </p>

        <form method="GET" action="{{ route('katalog') }}" class="mt-5 flex flex-col gap-2 sm:flex-row" role="search">
            <label for="q" class="sr-only">Kata kunci</label>
            <input id="q" name="q" type="search" value="{{ $filter['q'] ?? '' }}" placeholder="Cari judul atau deskripsi, mis. posyandu, jalan, puskesmas"
                   class="input sm:max-w-md">
            <label for="opd" class="sr-only">OPD</label>
            <select id="opd" name="opd" class="input sm:max-w-xs">
                <option value="">Semua OPD</option>
                @foreach($daftarOpd as $opd)
                    <option value="{{ $opd->singkatan }}" @selected(($filter['opd'] ?? '') === $opd->singkatan)>{{ $opd->nama }} ({{ $opd->datasets_count }})</option>
                @endforeach
            </select>
            <button class="btn btn-utama">Cari</button>
            @if(($filter['q'] ?? '') !== '' || ($filter['opd'] ?? '') !== '')
                <a href="{{ route('katalog') }}" class="btn">Reset</a>
            @endif
        </form>
    </section>

    <p class="mb-3 text-sm text-slate-500">{{ $datasets->total() }} dataset ditemukan</p>

    @if($datasets->isEmpty())
        <div class="kartu text-center text-slate-600">
            @if(($filter['q'] ?? '') !== '' || ($filter['opd'] ?? '') !== '')
                Tidak ada dataset yang cocok. Coba kata kunci lain atau pilih "Semua OPD".
            @else
                Belum ada dataset yang tayang.
            @endif
        </div>
    @else
        <ul class="grid gap-4 md:grid-cols-2">
            @foreach($datasets as $d)
                <li class="kartu flex flex-col">
                    <p class="text-xs font-medium uppercase tracking-wide text-brand-700">{{ $d->opd->nama }}</p>
                    <h2 class="mt-1 text-lg font-semibold leading-snug">
                        <a href="{{ route('katalog.show', $d->slug) }}" class="hover:text-brand-700 hover:underline">{{ $d->judul }}</a>
                    </h2>
                    <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-600">{{ $d->deskripsi }}</p>
                    <dl class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                        @if($d->periode)<div><dt class="inline">Periode:</dt> <dd class="inline">{{ $d->periode }}</dd></div>@endif
                        <div><dt class="inline">Baris:</dt> <dd class="inline">{{ number_format($d->jumlah_baris, 0, ',', '.') }}</dd></div>
                        <div><dt class="inline">Diperbarui:</dt> <dd class="inline">{{ $d->data_diperbarui_pada?->translatedFormat('j M Y') }}</dd></div>
                    </dl>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $datasets->links() }}</div>
    @endif
@endsection
