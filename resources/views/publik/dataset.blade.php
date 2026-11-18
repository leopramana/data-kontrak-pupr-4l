@extends('layouts.app')
@section('judul', $dataset->judul)

@section('konten')
    <nav class="mb-4 text-sm text-slate-500" aria-label="Remah roti">
        <a href="{{ route('katalog') }}" class="hover:underline">Katalog</a>
        <span aria-hidden="true">›</span>
        <a href="{{ route('katalog', ['opd' => $dataset->opd->singkatan]) }}" class="hover:underline">{{ $dataset->opd->singkatan }}</a>
    </nav>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-sm font-medium text-brand-700">{{ $dataset->opd->nama }}</p>
            <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">{{ $dataset->judul }}</h1>
            <p class="mt-3 whitespace-pre-line text-slate-700">{{ $dataset->deskripsi }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <a href="{{ route('katalog.unduh', $dataset->slug) }}" class="btn btn-utama">Unduh CSV</a>
            <a href="{{ route('api.datasets.show', $dataset->slug) }}" class="btn" title="Data dalam format JSON">API</a>
        </div>
    </div>

    @include('partials.metadata', ['dataset' => $dataset])

    <h2 class="mb-3 mt-8 text-lg font-semibold">Data</h2>
    @include('partials.tabel-data', [
        'namaKolom' => $dataset->kolom->pluck('nama')->all(),
        'tipe' => $dataset->kolom->pluck('tipe')->all(),
        'isiBaris' => $baris->getCollection()->pluck('isi'),
        'nomorAwal' => $baris->firstItem() ?? 1,
    ])
    <div class="mt-4">{{ $baris->links() }}</div>
@endsection
