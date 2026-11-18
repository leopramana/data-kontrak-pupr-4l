<?php

namespace Tests\Feature;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UnggahDatasetTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->operator = User::factory()->operator(Opd::factory()->create())->create();
    }

    private function unggah(UploadedFile $file, array $meta = [])
    {
        return $this->actingAs($this->operator)->post(route('dataset.store'), $meta + [
            'judul' => 'Jumlah Posyandu',
            'deskripsi' => 'Jumlah posyandu per kecamatan.',
            'periode' => '2025',
            'file' => $file,
        ]);
    }

    private function csv(string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('data.csv', $isi);
    }

    private function xlsx(array $baris, ?string $gabung = null): UploadedFile
    {
        $buku = new Spreadsheet;
        $buku->getActiveSheet()->fromArray($baris);
        if ($gabung) {
            $buku->getActiveSheet()->mergeCells($gabung);
        }
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($buku))->save($path);

        return new UploadedFile($path, 'data.xlsx', null, null, true);
    }

    public function test_alur_lengkap_unggah_csv_pratinjau_simpan(): void
    {
        $this->unggah($this->csv("kecamatan,tahun,jumlah\nPendopo,2025,12\nSaling,2025,7\n"))
            ->assertRedirect(route('pratinjau.show'));

        $this->get(route('pratinjau.show'))->assertOk()->assertSee('2 baris data')->assertSee('Pendopo');

        $this->post(route('pratinjau.simpan'))->assertRedirect();

        $dataset = Dataset::firstOrFail();
        $this->assertSame(StatusDataset::Draf, $dataset->status);
        $this->assertSame($this->operator->opd_id, $dataset->opd_id);
        $this->assertSame(2, $dataset->jumlah_baris);
        $this->assertSame(['kecamatan', 'tahun', 'jumlah'], $dataset->kolom->pluck('nama')->all());
        $this->assertSame(['teks', 'angka', 'angka'], $dataset->kolom->pluck('tipe')->all());
        $this->assertSame(['Pendopo', 2025, 12], $dataset->baris->first()->isi);
        $this->assertSame([], Storage::disk('local')->files('unggahan'), 'File sementara harus dihapus');

        // Ajukan ke walidata
        $this->post(route('dataset.ajukan', $dataset))->assertRedirect();
        $this->assertSame(StatusDataset::Diajukan, $dataset->fresh()->status);
    }

    public function test_csv_titik_koma_dari_excel_indonesia_terbaca(): void
    {
        $this->unggah($this->csv("\xEF\xBB\xBFkecamatan;jumlah\r\nPendopo;12\r\nSaling;7\r\n"))->assertRedirect(route('pratinjau.show'));
        $this->post(route('pratinjau.simpan'));

        $this->assertSame(['kecamatan', 'jumlah'], Dataset::firstOrFail()->kolom->pluck('nama')->all());
    }

    public function test_file_berisi_nik_ditolak_dan_tidak_disimpan(): void
    {
        $this->unggah($this->csv("kecamatan,nik_penerima\nPendopo,1671012345678901\n"))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('datasets', 0);
        $this->assertSame([], Storage::disk('local')->files('unggahan'));
    }

    public function test_file_berisi_nama_dan_alamat_warga_ditolak(): void
    {
        $this->unggah($this->csv("nama,alamat,jumlah\nBudi,Jl. Merdeka 1,1\n"))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('datasets', 0);
    }

    public function test_xlsx_dengan_sel_gabung_ditolak(): void
    {
        $this->unggah($this->xlsx([['kecamatan', 'jumlah'], ['Pendopo', 1]], 'A1:B1'))->assertSessionHasErrors('file');
    }

    public function test_xlsx_dengan_baris_judul_ditolak(): void
    {
        $this->unggah($this->xlsx([['DATA POSYANDU 2025', null], ['kecamatan', 'jumlah'], ['Pendopo', 1]]))
            ->assertSessionHasErrors('file');
    }

    public function test_xlsx_normal_terbaca_dengan_angka(): void
    {
        $this->unggah($this->xlsx([['kecamatan', 'panjang_km'], ['Pendopo', 12.5], ['Saling', 7]]))->assertRedirect(route('pratinjau.show'));
        $this->post(route('pratinjau.simpan'));

        $this->assertSame(['Pendopo', 12.5], Dataset::firstOrFail()->baris->first()->isi);
    }

    public function test_format_file_lain_ditolak(): void
    {
        $this->unggah(UploadedFile::fake()->create('data.pdf', 10, 'application/pdf'))->assertSessionHasErrors('file');
    }

    public function test_unggah_ulang_mengganti_isi_dan_kembali_ke_draf(): void
    {
        $dataset = Dataset::factory()->for($this->operator->opd)->status(StatusDataset::Dikembalikan)
            ->denganIsi(['a'], [['lama'], ['lama2'], ['lama3']])->create();

        $this->actingAs($this->operator)->put(route('dataset.update', $dataset), [
            'judul' => $dataset->judul, 'deskripsi' => 'Diperbaiki',
            'file' => $this->csv("kecamatan,jumlah\nPendopo,1\n"),
        ])->assertRedirect(route('pratinjau.show'));
        $this->post(route('pratinjau.simpan'));

        $dataset->refresh();
        $this->assertSame(StatusDataset::Draf, $dataset->status);
        $this->assertSame(1, $dataset->jumlah_baris);
        $this->assertSame(1, $dataset->baris()->count());
        $this->assertSame('Diperbaiki', $dataset->deskripsi);
    }
}
