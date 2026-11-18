<?php

namespace App\Http\Controllers\Panel;

use App\Enums\StatusDataset;
use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Opd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pemeriksaan oleh walidata (Fase 3). */
class PemeriksaanController extends Controller
{
    /** Antrean pemeriksaan (T3.1), bisa difilter status dan OPD. */
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'status' => ['nullable', Rule::enum(StatusDataset::class)],
            'opd' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $filter['status'] ?? StatusDataset::Diajukan->value;

        $datasets = Dataset::with('opd')
            ->where('status', $status)
            ->when($filter['opd'] ?? null, fn ($q, $opd) => $q->where('opd_id', $opd))
            ->cari($filter['q'] ?? null)
            ->orderBy($status === StatusDataset::Diajukan->value ? 'diajukan_pada' : 'updated_at', $status === StatusDataset::Diajukan->value ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $jumlah = Dataset::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('panel.pemeriksaan.index', [
            'datasets' => $datasets,
            'status' => StatusDataset::from($status),
            'jumlah' => $jumlah,
            'daftarOpd' => Opd::orderBy('nama')->get(),
        ]);
    }

    /** Setujui (T3.3): wajib konfirmasi bebas data pribadi. */
    public function setujui(Request $request, Dataset $dataset): RedirectResponse
    {
        Gate::authorize('periksa', $dataset);

        $request->validate(
            ['konfirmasi' => ['accepted']],
            ['konfirmasi.accepted' => 'Centang konfirmasi bahwa dataset tidak memuat NIK, nama, atau alamat warga.'],
        );

        $dataset->update([
            'status' => StatusDataset::Disetujui,
            'disetujui_oleh' => $request->user()->id,
            'disetujui_pada' => now(),
            'catatan_walidata' => null,
        ]);
        $dataset->catat('disetujui', $request->user());

        return redirect()->route('pemeriksaan.index')->with('sukses', "\"{$dataset->judul}\" disetujui dan sudah tayang di katalog.");
    }

    /** Kembalikan ke operator dengan catatan (T3.4). */
    public function kembalikan(Request $request, Dataset $dataset): RedirectResponse
    {
        Gate::authorize('periksa', $dataset);

        $data = $request->validate(
            ['catatan' => ['required', 'string', 'min:5', 'max:2000']],
            ['catatan.required' => 'Tuliskan catatan agar operator tahu apa yang harus diperbaiki.'],
        );

        $dataset->update(['status' => StatusDataset::Dikembalikan, 'catatan_walidata' => $data['catatan']]);
        $dataset->catat('dikembalikan', $request->user(), $data['catatan']);

        return redirect()->route('pemeriksaan.index')->with('sukses', "\"{$dataset->judul}\" dikembalikan ke operator.");
    }

    /** Tarik dari publik untuk keadaan darurat (T3.5). */
    public function tarik(Request $request, Dataset $dataset): RedirectResponse
    {
        Gate::authorize('tarik', $dataset);

        $data = $request->validate(
            ['catatan' => ['required', 'string', 'min:5', 'max:2000']],
            ['catatan.required' => 'Tuliskan alasan dataset ditarik.'],
        );

        $dataset->update([
            'status' => StatusDataset::Dikembalikan,
            'catatan_walidata' => $data['catatan'],
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
        ]);
        $dataset->catat('ditarik', $request->user(), $data['catatan']);

        return redirect()->route('dataset.show', $dataset)->with('sukses', 'Dataset sudah ditarik dan tidak lagi tampil di katalog maupun API.');
    }
}
