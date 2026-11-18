<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** API baca untuk dashboard Bupati (Fase 5). Hanya dataset yang sudah disetujui. */
class DatasetApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filter = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'opd' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $halaman = Dataset::tayang()
            ->with('opd')
            ->cari($filter['q'] ?? null)
            ->when($filter['opd'] ?? null, fn ($q, $s) => $q->whereHas('opd', fn ($o) => $o->where('singkatan', $s)))
            ->latest('disetujui_pada')
            ->paginate($filter['per_page'] ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => $halaman->getCollection()->map($this->metadata(...))->values(),
            'meta' => $this->meta($halaman),
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $filter = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:1000']]);

        $dataset = Dataset::tayang()->with(['opd', 'kolom'])->where('slug', $slug)->firstOrFail();
        $nama = $dataset->kolom->pluck('nama')->all();

        $halaman = $dataset->baris()->paginate($filter['per_page'] ?? 100)->withQueryString();

        return response()->json([
            'dataset' => $this->metadata($dataset) + [
                'kolom' => $dataset->kolom->map(fn ($k) => ['nama' => $k->nama, 'tipe' => $k->tipe])->values(),
            ],
            'data' => $halaman->getCollection()->map(fn ($b) => array_combine($nama, array_pad($b->isi, count($nama), null)))->values(),
            'meta' => $this->meta($halaman),
        ]);
    }

    private function metadata(Dataset $d): array
    {
        return [
            'slug' => $d->slug,
            'judul' => $d->judul,
            'deskripsi' => $d->deskripsi,
            'opd' => ['nama' => $d->opd->nama, 'singkatan' => $d->opd->singkatan],
            'periode' => $d->periode,
            'satuan' => $d->satuan,
            'frekuensi' => $d->frekuensi,
            'sumber' => $d->sumber,
            'jumlah_baris' => $d->jumlah_baris,
            'disetujui_pada' => $d->disetujui_pada?->toIso8601String(),
            'data_diperbarui_pada' => $d->data_diperbarui_pada?->toIso8601String(),
            'url' => route('katalog.show', $d->slug),
            'url_api' => route('api.datasets.show', $d->slug),
            'url_csv' => route('katalog.unduh', $d->slug),
        ];
    }

    private function meta($halaman): array
    {
        return [
            'halaman' => $halaman->currentPage(),
            'per_halaman' => $halaman->perPage(),
            'total' => $halaman->total(),
            'halaman_terakhir' => $halaman->lastPage(),
            'berikutnya' => $halaman->nextPageUrl(),
        ];
    }
}
