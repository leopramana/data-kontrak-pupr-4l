@extends('layouts.app')
@section('judul', 'Dataset OPD')

@section('konten')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Dataset {{ auth()->user()->opd?->nama }}</h1>
            <p class="mt-1 text-sm text-slate-600">Dataset tampil di katalog publik setelah disetujui walidata.</p>
        </div>
        <a href="{{ route('dataset.create') }}" class="btn btn-utama">+ Unggah dataset</a>
    </div>

    @if($datasets->isEmpty())
        <div class="kartu text-center text-slate-600">
            Belum ada dataset. Klik <strong>Unggah dataset</strong> untuk mulai dari file Excel atau CSV.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-2 font-medium">Judul</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2 text-right font-medium">Baris</th>
                        <th class="px-4 py-2 font-medium">Terakhir diubah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($datasets as $d)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('dataset.show', $d) }}" class="font-medium text-brand-700 hover:underline">{{ $d->judul }}</a>
                                @if($d->status === \App\Enums\StatusDataset::Dikembalikan && $d->catatan_walidata)
                                    <p class="mt-1 text-xs text-rose-700">Catatan walidata: {{ \Illuminate\Support\Str::limit($d->catatan_walidata, 120) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">@include('partials.status', ['status' => $d->status])</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($d->jumlah_baris, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $d->updated_at->translatedFormat('j M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $datasets->links() }}</div>
    @endif
@endsection
