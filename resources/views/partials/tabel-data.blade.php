{{--
    $namaKolom : list<string>
    $tipe      : list<string> ('angka' | 'teks')
    $isiBaris  : iterable<list<mixed>>
    $nomorAwal : int
--}}
<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th scope="col" class="px-3 py-2 text-right font-medium">#</th>
                @foreach($namaKolom as $i => $nama)
                    <th scope="col" class="whitespace-nowrap px-3 py-2 font-medium {{ ($tipe[$i] ?? '') === 'angka' ? 'text-right' : '' }}">{{ $nama }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($isiBaris as $n => $isi)
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-1.5 text-right text-slate-400 tabular-nums">{{ $nomorAwal + $loop->index }}</td>
                    @foreach($namaKolom as $i => $nama)
                        <td class="px-3 py-1.5 {{ ($tipe[$i] ?? '') === 'angka' ? 'text-right tabular-nums' : '' }}">{{ $isi[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($namaKolom) + 1 }}" class="px-3 py-6 text-center text-slate-500">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
