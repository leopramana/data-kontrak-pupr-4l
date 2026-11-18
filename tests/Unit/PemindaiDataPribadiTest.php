<?php

namespace Tests\Unit;

use App\Services\PemindaiDataPribadi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PemindaiDataPribadiTest extends TestCase
{
    private function pindai(array $kolom, array $baris = [['x']]): array
    {
        return (new PemindaiDataPribadi)->pindai($kolom, $baris);
    }

    public static function kolomDitolak(): array
    {
        return [
            ['NIK'], ['no_kk'], ['Nomor KK'], ['nomor kartu keluarga'], ['No. KTP'], ['nama'], ['Nama Lengkap'],
            ['nama_pasien'], ['Nama Kepala Keluarga'], ['alamat'], ['Alamat Rumah'], ['RT/RW'], ['kk'],
        ];
    }

    #[DataProvider('kolomDitolak')]
    public function test_kolom_data_pribadi_ditolak(string $kolom): void
    {
        $this->assertNotEmpty($this->pindai([$kolom])['tolak'], "Kolom '{$kolom}' seharusnya ditolak");
    }

    public static function kolomPeringatan(): array
    {
        return [['nama_kecamatan'], ['Nama Puskesmas'], ['alamat_kantor'], ['alamat puskesmas'], ['no_hp'], ['tanggal lahir']];
    }

    #[DataProvider('kolomPeringatan')]
    public function test_kolom_meragukan_hanya_peringatan(string $kolom): void
    {
        $hasil = $this->pindai([$kolom]);

        $this->assertEmpty($hasil['tolak'], "Kolom '{$kolom}' seharusnya tidak ditolak");
        $this->assertNotEmpty($hasil['peringatan'], "Kolom '{$kolom}' seharusnya diberi peringatan");
    }

    public static function kolomAman(): array
    {
        return [['kecamatan'], ['jumlah_kk'], ['Jumlah Kartu Keluarga'], ['tahun'], ['jumlah_posyandu'], ['panjang_km']];
    }

    #[DataProvider('kolomAman')]
    public function test_kolom_agregat_lolos_tanpa_catatan(string $kolom): void
    {
        $this->assertSame(['tolak' => [], 'peringatan' => []], $this->pindai([$kolom]));
    }

    public function test_isi_16_digit_ditolak_walau_nama_kolom_aman(): void
    {
        $hasil = $this->pindai(['kode', 'jumlah'], [['A1', 3], ['1671012345678901', 4], ['1671 0123 4567 8902', 5]]);

        $this->assertCount(1, $hasil['tolak']);
        $this->assertStringContainsString('baris data ke-2, 3', $hasil['tolak'][0]);
    }

    public function test_angka_biasa_tidak_dianggap_nik(): void
    {
        $this->assertSame([], $this->pindai(['jumlah'], [[123456789012345], ['2025'], [3.5]])['tolak']);
    }
}
