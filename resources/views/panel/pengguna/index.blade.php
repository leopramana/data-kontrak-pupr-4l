@extends('layouts.app')
@section('judul', 'Pengguna')

@section('konten')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Pengguna</h1>
            <p class="mt-1 text-sm text-slate-600">Akun operator OPD, walidata, dan admin. Tidak ada pendaftaran mandiri.</p>
        </div>
        <a href="{{ route('pengguna.create') }}" class="btn btn-utama">+ Tambah akun</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-2 font-medium">Nama</th>
                    <th class="px-4 py-2 font-medium">Email</th>
                    <th class="px-4 py-2 font-medium">Peran</th>
                    <th class="px-4 py-2 font-medium">OPD</th>
                    <th class="px-4 py-2 font-medium">Status</th>
                    <th class="px-4 py-2"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($pengguna as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">{{ $p->name }}</td>
                        <td class="px-4 py-3">{{ $p->email }}</td>
                        <td class="px-4 py-3">{{ $p->role->label() }}</td>
                        <td class="px-4 py-3">{{ $p->opd?->singkatan ?? '–' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $p->aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('pengguna.edit', $p) }}" class="text-brand-700 hover:underline">Ubah</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pengguna->links() }}</div>
@endsection
