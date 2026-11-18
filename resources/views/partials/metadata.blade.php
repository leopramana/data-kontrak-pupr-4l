<dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 rounded-lg border border-slate-200 bg-white p-5 text-sm sm:grid-cols-3 lg:grid-cols-6">
    @foreach([
        'Periode' => $dataset->periode,
        'Satuan' => $dataset->satuan,
        'Frekuensi' => $dataset->frekuensi,
        'Sumber' => $dataset->sumber,
        'Jumlah baris' => number_format($dataset->jumlah_baris, 0, ',', '.'),
        'Data diperbarui' => $dataset->data_diperbarui_pada?->translatedFormat('j F Y'),
    ] as $label => $nilai)
        <div>
            <dt class="text-slate-500">{{ $label }}</dt>
            <dd class="mt-0.5 font-medium">{{ filled($nilai) ? $nilai : '–' }}</dd>
        </div>
    @endforeach
</dl>
