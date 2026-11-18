<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Halaman publik (Fase 4). Hanya dataset berstatus disetujui. */
class KatalogController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'opd' => ['nullable', 'string', 'max:50'],
        ]);

        $datasets = Dataset::tayang()
            ->with('opd')
            ->cari($filter['q'] ?? null)
            ->when($filter['opd'] ?? null, fn ($q, $singkatan) => $q->whereHas('opd', fn ($o) => $o->where('singkatan', $singkatan)))
            ->latest('disetujui_pada')
            ->paginate(12)
            ->withQueryString();

        $daftarOpd = Opd::whereHas('datasets', fn ($q) => $q->tayang())
            ->withCount(['datasets' => fn ($q) => $q->tayang()])
            ->orderBy('nama')
            ->get();

        return view('publik.katalog', compact('datasets', 'daftarOpd', 'filter'));
    }

    public function show(string $slug): View
    {
        $dataset = Dataset::tayang()->with(['opd', 'kolom'])->where('slug', $slug)->firstOrFail();
        $baris = $dataset->baris()->paginate(50);

        return view('publik.dataset', compact('dataset', 'baris'));
    }

    /** Unduh CSV yang dibuat dari database, bukan file asli unggahan (T4.4). */
    public function unduh(string $slug): StreamedResponse
    {
        $dataset = Dataset::tayang()->with('kolom')->where('slug', $slug)->firstOrFail();

        return response()->streamDownload(function () use ($dataset) {
            $keluar = fopen('php://output', 'w');
            fwrite($keluar, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($keluar, $dataset->kolom->pluck('nama')->map(self::amankanSel(...))->all(), ',', '"', '');

            $dataset->baris()->select(['id', 'isi'])->chunkById(1000, function ($potongan) use ($keluar) {
                foreach ($potongan as $baris) {
                    fputcsv($keluar, array_map(self::amankanSel(...), $baris->isi), ',', '"', '');
                }
            });

            fclose($keluar);
        }, $dataset->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Cegah "CSV injection": teks yang diawali =, +, -, @ bisa dijalankan sebagai rumus oleh Excel. */
    public static function amankanSel(mixed $nilai): mixed
    {
        if (is_string($nilai) && $nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$nilai;
        }

        return $nilai;
    }
}
