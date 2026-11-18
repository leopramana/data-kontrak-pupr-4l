<?php

namespace App\Http\Controllers\Panel;

use App\Enums\StatusDataset;
use App\Exceptions\FileTidakValid;
use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\PembacaTabel;
use App\Services\PemindaiDataPribadi;
use App\Services\PenyimpanIsiDataset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Langkah 2 unggah: pratinjau tabel, lalu simpan atau batal (T2.4). */
class PratinjauController extends Controller
{
    public const SESI = 'unggahan';

    public function __construct(
        private PembacaTabel $pembaca,
        private PemindaiDataPribadi $pemindai,
        private PenyimpanIsiDataset $penyimpan,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $unggahan = $this->unggahan($request)) {
            return redirect()->route('dataset.index');
        }

        $hasil = $this->bacaDanPindai(Storage::disk('local')->path($unggahan['path']), $unggahan['ekstensi']);

        if ($hasil['galat'] !== []) {
            self::hapusFileSementara($request);

            return redirect()->route('dataset.index')->withErrors(['file' => $hasil['galat']]);
        }

        return view('panel.dataset.pratinjau', [
            'unggahan' => $unggahan,
            'tabel' => $hasil['tabel'],
            'peringatan' => $hasil['peringatan'],
            'dataset' => $unggahan['dataset_id'] ? Dataset::find($unggahan['dataset_id']) : null,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        if (! $unggahan = $this->unggahan($request)) {
            return redirect()->route('dataset.index');
        }

        $user = $request->user();
        $dataset = $unggahan['dataset_id'] ? Dataset::findOrFail($unggahan['dataset_id']) : null;
        $dataset ? Gate::authorize('update', $dataset) : Gate::authorize('create', Dataset::class);

        // Baca dan pindai ulang: jangan percaya hasil langkah sebelumnya.
        $hasil = $this->bacaDanPindai(Storage::disk('local')->path($unggahan['path']), $unggahan['ekstensi']);

        if ($hasil['galat'] !== []) {
            self::hapusFileSementara($request);

            return redirect()->route('dataset.index')->withErrors(['file' => $hasil['galat']]);
        }

        $dataset = DB::transaction(function () use ($dataset, $unggahan, $user, $hasil) {
            $baru = $dataset === null;
            $dataset ??= new Dataset(['opd_id' => $user->opd_id, 'dibuat_oleh' => $user->id]);

            $dataset->fill($unggahan['meta']);
            $dataset->fill([
                'status' => StatusDataset::Draf,
                'nama_file_asli' => $unggahan['nama_file'],
                'hasil_pemindaian' => ['peringatan' => $hasil['peringatan']],
            ]);

            if ($baru || $dataset->isDirty('judul')) {
                $dataset->slug = Dataset::buatSlug($dataset->judul, $dataset->id);
            }

            $dataset->save();
            $this->penyimpan->simpan($dataset, $hasil['tabel']);
            $dataset->catat($baru ? 'dibuat' : 'diunggah_ulang', $user, $unggahan['nama_file']);

            return $dataset;
        });

        self::hapusFileSementara($request);

        return redirect()->route('dataset.show', $dataset)
            ->with('sukses', 'Dataset disimpan sebagai draf. Periksa sekali lagi, lalu klik "Ajukan ke walidata".');
    }

    public function batal(Request $request): RedirectResponse
    {
        $datasetId = $this->unggahan($request)['dataset_id'] ?? null;
        self::hapusFileSementara($request);

        return $datasetId
            ? redirect()->route('dataset.show', $datasetId)->with('sukses', 'Unggahan dibatalkan.')
            : redirect()->route('dataset.index')->with('sukses', 'Unggahan dibatalkan.');
    }

    /**
     * @return array{tabel: ?array, peringatan: list<string>, galat: list<string>}
     */
    public function bacaDanPindai(string $path, string $ekstensi): array
    {
        try {
            $tabel = $this->pembaca->baca($path, $ekstensi);
        } catch (FileTidakValid $e) {
            return ['tabel' => null, 'peringatan' => [], 'galat' => $e->pesan];
        }

        $pindai = $this->pemindai->pindai($tabel['kolom'], $tabel['baris']);

        if ($pindai['tolak'] !== []) {
            return [
                'tabel' => null,
                'peringatan' => [],
                'galat' => ['File ditolak karena tampaknya memuat data pribadi warga:', ...$pindai['tolak']],
            ];
        }

        return [
            'tabel' => $tabel,
            'peringatan' => [...$tabel['peringatan'], ...$pindai['peringatan']],
            'galat' => [],
        ];
    }

    public static function hapusFileSementara(Request $request): void
    {
        if ($path = $request->session()->pull(self::SESI)['path'] ?? null) {
            Storage::disk('local')->delete($path);
        }
    }

    private function unggahan(Request $request): ?array
    {
        $unggahan = $request->session()->get(self::SESI);

        if (! $unggahan || ! Storage::disk('local')->exists($unggahan['path'])) {
            return null;
        }

        return $unggahan;
    }
}
