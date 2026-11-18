<?php

namespace Tests\Feature;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemeriksaanTest extends TestCase
{
    use RefreshDatabase;

    private User $walidata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walidata = User::factory()->walidata()->create();
    }

    public function test_antrean_menampilkan_dataset_yang_diajukan(): void
    {
        Dataset::factory()->status(StatusDataset::Diajukan)->create(['judul' => 'Menunggu Diperiksa']);
        Dataset::factory()->create(['judul' => 'Masih Draf']);

        $this->actingAs($this->walidata)->get(route('pemeriksaan.index'))
            ->assertOk()->assertSee('Menunggu Diperiksa')->assertDontSee('Masih Draf');
    }

    public function test_setujui_wajib_konfirmasi_bebas_data_pribadi(): void
    {
        $dataset = Dataset::factory()->status(StatusDataset::Diajukan)->create();

        $this->actingAs($this->walidata)->post(route('pemeriksaan.setujui', $dataset))->assertSessionHasErrors('konfirmasi');
        $this->assertSame(StatusDataset::Diajukan, $dataset->fresh()->status);

        $this->actingAs($this->walidata)->post(route('pemeriksaan.setujui', $dataset), ['konfirmasi' => '1'])->assertRedirect();
        $dataset->refresh();
        $this->assertSame(StatusDataset::Disetujui, $dataset->status);
        $this->assertSame($this->walidata->id, $dataset->disetujui_oleh);
        $this->assertDatabaseHas('dataset_riwayat', ['dataset_id' => $dataset->id, 'aksi' => 'disetujui']);
        $this->get(route('katalog.show', $dataset->slug))->assertOk();
    }

    public function test_kembalikan_wajib_catatan(): void
    {
        $dataset = Dataset::factory()->status(StatusDataset::Diajukan)->create();

        $this->actingAs($this->walidata)->post(route('pemeriksaan.kembalikan', $dataset))->assertSessionHasErrors('catatan');

        $this->actingAs($this->walidata)->post(route('pemeriksaan.kembalikan', $dataset), ['catatan' => 'Kolom tahun kosong'])->assertRedirect();
        $this->assertSame(StatusDataset::Dikembalikan, $dataset->fresh()->status);
        $this->assertSame('Kolom tahun kosong', $dataset->fresh()->catatan_walidata);
    }

    public function test_draf_belum_diajukan_tidak_bisa_disetujui(): void
    {
        $dataset = Dataset::factory()->create();

        $this->actingAs($this->walidata)->post(route('pemeriksaan.setujui', $dataset), ['konfirmasi' => '1'])->assertForbidden();
    }

    public function test_tarik_langsung_menghilangkan_dari_katalog_dan_api(): void
    {
        $dataset = Dataset::factory()->disetujui()->denganIsi()->create();
        $this->get(route('katalog.show', $dataset->slug))->assertOk();

        $this->actingAs($this->walidata)->post(route('pemeriksaan.tarik', $dataset), ['catatan' => 'Ada nama warga di kolom C'])->assertRedirect();

        $this->assertSame(StatusDataset::Dikembalikan, $dataset->fresh()->status);
        $this->get(route('katalog.show', $dataset->slug))->assertNotFound();
        $this->getJson(route('api.datasets.show', $dataset->slug))->assertNotFound();
    }

    public function test_operator_tidak_bisa_menyetujui_sendiri(): void
    {
        $dataset = Dataset::factory()->status(StatusDataset::Diajukan)->create();
        $operator = User::factory()->operator($dataset->opd)->create();

        $this->actingAs($operator)->post(route('pemeriksaan.setujui', $dataset), ['konfirmasi' => '1'])->assertForbidden();
    }
}
