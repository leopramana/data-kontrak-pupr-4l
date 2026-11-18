<?php

namespace Tests\Feature;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KatalogPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_dataset_disetujui_yang_tampil_di_katalog(): void
    {
        Dataset::factory()->disetujui()->create(['judul' => 'Dataset Tayang']);
        foreach ([StatusDataset::Draf, StatusDataset::Diajukan, StatusDataset::Dikembalikan] as $status) {
            Dataset::factory()->status($status)->create(['judul' => 'Dataset '.$status->value]);
        }

        $this->get(route('katalog'))
            ->assertOk()
            ->assertSee('Dataset Tayang')
            ->assertDontSee('Dataset draf')
            ->assertDontSee('Dataset diajukan')
            ->assertDontSee('Dataset dikembalikan');
    }

    public function test_dataset_belum_disetujui_tidak_bisa_dibuka_lewat_url_csv_maupun_api(): void
    {
        $dataset = Dataset::factory()->status(StatusDataset::Diajukan)->denganIsi()->create();

        $this->get(route('katalog.show', $dataset->slug))->assertNotFound();
        $this->get(route('katalog.unduh', $dataset->slug))->assertNotFound();
        $this->getJson(route('api.datasets.show', $dataset->slug))->assertNotFound();
        $this->getJson(route('api.datasets.index'))->assertJsonCount(0, 'data');
    }

    public function test_pencarian_dan_filter_opd(): void
    {
        $a = Dataset::factory()->disetujui()->create(['judul' => 'Jumlah Posyandu']);
        Dataset::factory()->disetujui()->create(['judul' => 'Panjang Jalan']);

        $this->get(route('katalog', ['q' => 'posyandu']))->assertSee('Jumlah Posyandu')->assertDontSee('Panjang Jalan');
        $this->get(route('katalog', ['opd' => $a->opd->singkatan]))->assertSee('Jumlah Posyandu')->assertDontSee('Panjang Jalan');
    }

    public function test_unduh_csv_berisi_data_dan_aman_dari_rumus_excel(): void
    {
        $dataset = Dataset::factory()->disetujui()
            ->denganIsi(['kecamatan', 'catatan'], [['Pendopo', '=HYPERLINK("http://contoh")'], ['Saling', 'aman']])
            ->create();

        $isi = $this->get(route('katalog.unduh', $dataset->slug))->assertOk()->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBFkecamatan,catatan\n", $isi);
        $this->assertStringContainsString('Pendopo,"\'=HYPERLINK(""http://contoh"")"', $isi);
        $this->assertStringContainsString('Saling,aman', $isi);
    }

    public function test_api_mengembalikan_data_sebagai_objek_per_baris(): void
    {
        $dataset = Dataset::factory()->disetujui()->denganIsi(['kecamatan', 'jumlah'], [['Pendopo', 10], ['Saling', 5]])->create();

        $this->getJson(route('api.datasets.show', $dataset->slug))
            ->assertOk()
            ->assertJsonPath('dataset.slug', $dataset->slug)
            ->assertJsonPath('data.0', ['kecamatan' => 'Pendopo', 'jumlah' => 10])
            ->assertJsonPath('meta.total', 2);
    }
}
