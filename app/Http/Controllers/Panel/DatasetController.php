<?php

namespace App\Http\Controllers\Panel;

use App\Enums\StatusDataset;
use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\PembacaTabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DatasetController extends Controller
{
    /** Daftar dataset milik OPD operator (T2.7). */
    public function index(Request $request): View
    {
        $datasets = Dataset::where('opd_id', $request->user()->opd_id)
            ->latest('updated_at')
            ->paginate(20);

        return view('panel.dataset.index', compact('datasets'));
    }

    public function create(): View
    {
        Gate::authorize('create', Dataset::class);

        return view('panel.dataset.form', ['dataset' => new Dataset]);
    }

    /** Langkah 1 unggah: simpan file sementara lalu tampilkan pratinjau. */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Dataset::class);

        $meta = $request->validate($this->aturan(fileWajib: true));

        return $this->keLangkahPratinjau($request, $meta, null);
    }

    public function show(Dataset $dataset): View
    {
        Gate::authorize('view', $dataset);

        $dataset->load(['opd', 'pembuat', 'penyetuju', 'kolom', 'riwayat.user']);
        $baris = $dataset->baris()->paginate(50);

        return view('panel.dataset.show', compact('dataset', 'baris'));
    }

    public function edit(Dataset $dataset): View
    {
        Gate::authorize('update', $dataset);

        return view('panel.dataset.form', compact('dataset'));
    }

    /** Ubah metadata; bila ada file baru, lanjut ke pratinjau (T2.6). */
    public function update(Request $request, Dataset $dataset): RedirectResponse
    {
        Gate::authorize('update', $dataset);

        $meta = $request->validate($this->aturan(fileWajib: false));

        if ($request->hasFile('file')) {
            return $this->keLangkahPratinjau($request, $meta, $dataset);
        }

        $dataset->fill(collect($meta)->except('file')->all());

        if ($dataset->isDirty('judul')) {
            $dataset->slug = Dataset::buatSlug($dataset->judul, $dataset->id);
        }

        $dataset->save();
        $dataset->catat('metadata_diubah', $request->user());

        return redirect()->route('dataset.show', $dataset)->with('sukses', 'Metadata dataset disimpan.');
    }

    /** Ajukan ke walidata (T2.8). */
    public function ajukan(Request $request, Dataset $dataset): RedirectResponse
    {
        Gate::authorize('ajukan', $dataset);

        $dataset->update(['status' => StatusDataset::Diajukan, 'diajukan_pada' => now()]);
        $dataset->catat('diajukan', $request->user());

        return redirect()->route('dataset.show', $dataset)->with('sukses', 'Dataset diajukan. Walidata akan memeriksanya.');
    }

    public function destroy(Dataset $dataset): RedirectResponse
    {
        Gate::authorize('delete', $dataset);

        $dataset->delete();

        return redirect()->route('dataset.index')->with('sukses', 'Draf dataset dihapus.');
    }

    private function aturan(bool $fileWajib): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['required', 'string', 'max:2000'],
            'periode' => ['nullable', 'string', 'max:100'],
            'satuan' => ['nullable', 'string', 'max:100'],
            'frekuensi' => ['nullable', Rule::in(Dataset::FREKUENSI)],
            'sumber' => ['nullable', 'string', 'max:255'],
            'file' => [$fileWajib ? 'required' : 'nullable', 'file', 'max:10240', 'extensions:'.implode(',', PembacaTabel::EKSTENSI)],
        ];
    }

    private function keLangkahPratinjau(Request $request, array $meta, ?Dataset $dataset): RedirectResponse
    {
        PratinjauController::hapusFileSementara($request);

        $file = $request->file('file');
        $ekstensi = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('unggahan', Str::uuid().'.'.$ekstensi, 'local');

        $request->session()->put(PratinjauController::SESI, [
            'path' => $path,
            'ekstensi' => $ekstensi,
            'nama_file' => Str::limit($file->getClientOriginalName(), 200, ''),
            'meta' => collect($meta)->except('file')->all(),
            'dataset_id' => $dataset?->id,
        ]);

        // Baca & pindai sekarang supaya kesalahan langsung tampil di form.
        $hasil = app(PratinjauController::class)->bacaDanPindai(Storage::disk('local')->path($path), $ekstensi);

        if ($hasil['galat'] !== []) {
            PratinjauController::hapusFileSementara($request);

            return back()->withInput()->withErrors(['file' => $hasil['galat']]);
        }

        return redirect()->route('pratinjau.show');
    }
}
