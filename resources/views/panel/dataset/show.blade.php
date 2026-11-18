@extends('layouts.app')
@section('judul', $dataset->judul)

@section('konten')
    @php($sembunyikanGalatUmum = true)
    @php($peringatan = $dataset->hasil_pemindaian['peringatan'] ?? [])

    <a href="{{ auth()->user()->isOperator() ? route('dataset.index') : route('pemeriksaan.index') }}" class="text-sm text-slate-500 hover:underline">← Kembali</a>

    <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                @include('partials.status', ['status' => $dataset->status])
                <span class="text-sm text-slate-500">{{ $dataset->opd->nama }}</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold">{{ $dataset->judul }}</h1>
            <p class="mt-2 whitespace-pre-line text-slate-700">{{ $dataset->deskripsi }}</p>
            <p class="mt-2 text-xs text-slate-500">
                Diunggah oleh {{ $dataset->pembuat->name }} · file: {{ $dataset->nama_file_asli ?? '–' }}
                @if($dataset->status === \App\Enums\StatusDataset::Disetujui)
                    · disetujui {{ $dataset->penyetuju?->name }}, {{ $dataset->disetujui_pada?->translatedFormat('j M Y H:i') }}
                    · <a href="{{ route('katalog.show', $dataset->slug) }}" class="text-brand-700 underline">lihat di katalog</a>
                @endif
            </p>
        </div>

        {{-- Aksi operator --}}
        <div class="flex shrink-0 flex-wrap gap-2">
            @can('update', $dataset)
                <a href="{{ route('dataset.edit', $dataset) }}" class="btn">Ubah / unggah ulang</a>
            @endcan
            @can('ajukan', $dataset)
                <form method="POST" action="{{ route('dataset.ajukan', $dataset) }}">
                    @csrf
                    <button class="btn btn-utama">Ajukan ke walidata</button>
                </form>
            @endcan
            @can('delete', $dataset)
                <form method="POST" action="{{ route('dataset.destroy', $dataset) }}" onsubmit="return confirm('Hapus draf ini? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-bahaya">Hapus draf</button>
                </form>
            @endcan
        </div>
    </div>

    @if($dataset->catatan_walidata && $dataset->status === \App\Enums\StatusDataset::Dikembalikan)
        <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <p class="font-medium">Catatan walidata</p>
            <p class="mt-1 whitespace-pre-line">{{ $dataset->catatan_walidata }}</p>
        </div>
    @endif

    @if($dataset->status === \App\Enums\StatusDataset::Diajukan && auth()->user()->isOperator())
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Dataset sedang menunggu pemeriksaan walidata dan belum bisa diubah.
        </div>
    @endif

    @if($peringatan)
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-medium">Peringatan dari pemindaian otomatis</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach($peringatan as $p)<li>{{ $p }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Aksi walidata (T3.3, T3.4, T3.5) --}}
    @can('periksa', $dataset)
        <section class="kartu mt-6 border-brand-200" aria-labelledby="judul-periksa">
            <h2 id="judul-periksa" class="font-semibold">Keputusan pemeriksaan</h2>
            <p class="mt-1 text-sm text-slate-600">Periksa seluruh isi tabel di bawah sebelum memutuskan.</p>

            <div class="mt-4 grid gap-6 md:grid-cols-2">
                <form method="POST" action="{{ route('pemeriksaan.setujui', $dataset) }}" class="space-y-3">
                    @csrf
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" name="konfirmasi" value="1" required class="mt-0.5 rounded border-slate-300">
                        <span>Saya sudah memastikan dataset ini <strong>tidak memuat NIK, nama, atau alamat warga</strong>.</span>
                    </label>
                    @error('konfirmasi')<p class="galat">{{ $message }}</p>@enderror
                    <button class="btn btn-utama">Setujui dan tayangkan</button>
                </form>

                <form method="POST" action="{{ route('pemeriksaan.kembalikan', $dataset) }}" class="space-y-3">
                    @csrf
                    <label for="catatan" class="label">Catatan untuk operator</label>
                    <textarea id="catatan" name="catatan" rows="3" required minlength="5" class="input"
                              placeholder="Apa yang perlu diperbaiki?">{{ old('catatan') }}</textarea>
                    @error('catatan')<p class="galat">{{ $message }}</p>@enderror
                    <button class="btn btn-bahaya">Kembalikan ke operator</button>
                </form>
            </div>
        </section>
    @endcan

    @can('tarik', $dataset)
        <details class="kartu mt-6">
            <summary class="cursor-pointer font-medium text-rose-700">Tarik dataset dari publik</summary>
            <form method="POST" action="{{ route('pemeriksaan.tarik', $dataset) }}" class="mt-3 space-y-3"
                  onsubmit="return confirm('Tarik dataset ini dari katalog dan API sekarang?')">
                @csrf
                <p class="text-sm text-slate-600">Gunakan bila ternyata ada data pribadi atau kesalahan serius. Dataset langsung hilang dari katalog dan API, lalu kembali ke operator.</p>
                <label for="alasan" class="label">Alasan</label>
                <textarea id="alasan" name="catatan" rows="2" required minlength="5" class="input">{{ old('catatan') }}</textarea>
                @error('catatan')<p class="galat">{{ $message }}</p>@enderror
                <button class="btn btn-bahaya">Tarik sekarang</button>
            </form>
        </details>
    @endcan

    @include('partials.metadata', ['dataset' => $dataset])

    <h2 class="mb-3 mt-8 text-lg font-semibold">Isi data</h2>
    @include('partials.tabel-data', [
        'namaKolom' => $dataset->kolom->pluck('nama')->all(),
        'tipe' => $dataset->kolom->pluck('tipe')->all(),
        'isiBaris' => $baris->getCollection()->pluck('isi'),
        'nomorAwal' => $baris->firstItem() ?? 1,
    ])
    <div class="mt-4">{{ $baris->links() }}</div>

    <h2 class="mb-3 mt-8 text-lg font-semibold">Riwayat</h2>
    <ol class="kartu space-y-2 text-sm">
        @foreach($dataset->riwayat as $r)
            <li class="flex flex-col gap-0.5 sm:flex-row sm:gap-3">
                <span class="w-36 shrink-0 text-slate-500">{{ $r->created_at->translatedFormat('j M Y H:i') }}</span>
                <span>
                    <strong>{{ $r->label() }}</strong> oleh {{ $r->user?->name ?? 'sistem' }}
                    @if($r->catatan)<span class="text-slate-600">: {{ $r->catatan }}</span>@endif
                </span>
            </li>
        @endforeach
    </ol>
@endsection
