@extends('layouts.app')
@section('judul', 'Pemeriksaan')

@section('konten')
    <h1 class="text-2xl font-semibold">Pemeriksaan dataset</h1>
    <p class="mt-1 text-sm text-slate-600">Dataset baru tampil ke publik setelah Anda setujui.</p>

    <nav class="mt-6 flex flex-wrap gap-1 border-b border-slate-200" aria-label="Status">
        @foreach(\App\Enums\StatusDataset::cases() as $s)
            <a href="{{ route('pemeriksaan.index', ['status' => $s->value] + request()->only('opd', 'q')) }}"
               class="-mb-px border-b-2 px-3 py-2 text-sm font-medium {{ $status === $s ? 'border-brand-600 text-brand-800' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
               @if($status === $s) aria-current="page" @endif>
                {{ $s->label() }}
                <span class="ml-1 rounded-full bg-slate-100 px-2 text-xs text-slate-600">{{ $jumlah[$s->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="mt-4 flex flex-col gap-2 sm:flex-row">
        <input type="hidden" name="status" value="{{ $status->value }}">
        <label for="q" class="sr-only">Kata kunci</label>
        <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari judul…" class="input sm:max-w-xs">
        <label for="opd" class="sr-only">OPD</label>
        <select id="opd" name="opd" class="input sm:max-w-xs">
            <option value="">Semua OPD</option>
            @foreach($daftarOpd as $opd)
                <option value="{{ $opd->id }}" @selected((string) request('opd') === (string) $opd->id)>{{ $opd->nama }}</option>
            @endforeach
        </select>
        <button class="btn">Saring</button>
    </form>

    @if($datasets->isEmpty())
        <div class="kartu mt-4 text-center text-slate-600">
            {{ $status === \App\Enums\StatusDataset::Diajukan ? 'Tidak ada dataset yang menunggu pemeriksaan.' : 'Tidak ada dataset dengan status ini.' }}
        </div>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-2 font-medium">Judul</th>
                        <th class="px-4 py-2 font-medium">OPD</th>
                        <th class="px-4 py-2 text-right font-medium">Baris</th>
                        <th class="px-4 py-2 font-medium">Peringatan</th>
                        <th class="px-4 py-2 font-medium">{{ $status === \App\Enums\StatusDataset::Diajukan ? 'Diajukan' : 'Diubah' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($datasets as $d)
                        @php($nPeringatan = count($d->hasil_pemindaian['peringatan'] ?? []))
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3"><a href="{{ route('dataset.show', $d) }}" class="font-medium text-brand-700 hover:underline">{{ $d->judul }}</a></td>
                            <td class="px-4 py-3">{{ $d->opd->singkatan }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($d->jumlah_baris, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @if($nPeringatan)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ $nPeringatan }} peringatan</span>
                                @else
                                    <span class="text-slate-400">–</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                {{ ($status === \App\Enums\StatusDataset::Diajukan ? $d->diajukan_pada : $d->updated_at)?->translatedFormat('j M Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $datasets->links() }}</div>
    @endif
@endsection
