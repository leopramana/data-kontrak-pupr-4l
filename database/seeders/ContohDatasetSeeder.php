<?php

namespace Database\Seeders;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use App\Models\Opd;
use App\Models\User;
use App\Services\PenyimpanIsiDataset;
use Illuminate\Database\Seeder;

/**
 * Dataset CONTOH untuk uji coba di komputer lokal. Angka-angkanya karangan, BUKAN data resmi.
 * Hanya dijalankan pada APP_ENV=local (lihat DatabaseSeeder).
 */
class ContohDatasetSeeder extends Seeder
{
    private const KECAMATAN = [
        'Tebing Tinggi', 'Talang Padang', 'Pendopo', 'Ulu Musi', 'Lintang Kanan',
        'Muara Pinang', 'Pasemah Air Keruh', 'Saling', 'Sikap Dalam', 'Pendopo Barat',
    ];

    public function run(PenyimpanIsiDataset $penyimpan): void
    {
        $pupr = Opd::where('singkatan', 'PUPR')->first();
        $dinkes = Opd::where('singkatan', 'DINKES')->first();
        $opPupr = User::where('email', 'operator.pupr@katalog.test')->first();
        $opDinkes = User::where('email', 'operator.dinkes@katalog.test')->first();
        $walidata = User::where('email', 'walidata@katalog.test')->first();

        mt_srand(2026);
        $daftar = [
            [$dinkes, $opDinkes, StatusDataset::Disetujui, 'Jumlah Posyandu per Kecamatan', 'unit', 'Tahunan',
                ['kecamatan', 'tahun', 'jumlah_posyandu', 'posyandu_aktif'],
                fn ($k) => [$k, 2025, $n = mt_rand(12, 40), mt_rand(8, $n)]],
            [$pupr, $opPupr, StatusDataset::Disetujui, 'Panjang Jalan Kabupaten menurut Kondisi', 'km', 'Tahunan',
                ['kecamatan', 'tahun', 'baik_km', 'sedang_km', 'rusak_km'],
                fn ($k) => [$k, 2025, mt_rand(50, 400) / 10, mt_rand(20, 200) / 10, mt_rand(10, 150) / 10]],
            [$dinkes, $opDinkes, StatusDataset::Diajukan, 'Jumlah Tenaga Kesehatan per Puskesmas', 'orang', 'Tahunan',
                ['nama_puskesmas', 'kecamatan', 'dokter', 'bidan', 'perawat'],
                fn ($k) => ['Puskesmas '.$k, $k, mt_rand(1, 4), mt_rand(5, 20), mt_rand(6, 25)]],
            [$pupr, $opPupr, StatusDataset::Draf, 'Jumlah Jembatan menurut Kondisi', 'unit', 'Tahunan',
                ['kecamatan', 'tahun', 'baik', 'rusak_ringan', 'rusak_berat'],
                fn ($k) => [$k, 2025, mt_rand(3, 20), mt_rand(0, 6), mt_rand(0, 3)]],
        ];

        foreach ($daftar as [$opd, $operator, $status, $judul, $satuan, $frekuensi, $kolom, $buatBaris]) {
            $judul = '[CONTOH] '.$judul;

            if (Dataset::where('judul', $judul)->exists()) {
                continue;
            }

            $dataset = Dataset::create([
                'opd_id' => $opd->id,
                'judul' => $judul,
                'slug' => Dataset::buatSlug($judul),
                'deskripsi' => 'Data contoh untuk uji coba aplikasi. Angka-angka ini karangan dan BUKAN data resmi.',
                'periode' => '2025',
                'satuan' => $satuan,
                'frekuensi' => $frekuensi,
                'sumber' => 'Data contoh',
                'status' => StatusDataset::Draf,
                'nama_file_asli' => 'contoh.xlsx',
                'hasil_pemindaian' => ['peringatan' => in_array('nama_puskesmas', $kolom, true)
                    ? ['Kolom "nama_puskesmas": pastikan isinya nama tempat/lembaga, bukan nama warga.'] : []],
                'dibuat_oleh' => $operator->id,
            ]);

            $penyimpan->simpan($dataset, [
                'kolom' => $kolom,
                'tipe' => array_map(fn ($n) => in_array($n, ['kecamatan', 'nama_puskesmas'], true) ? 'teks' : 'angka', $kolom),
                'baris' => array_map($buatBaris, self::KECAMATAN),
            ]);
            $dataset->catat('dibuat', $operator, 'contoh.xlsx');

            if ($status !== StatusDataset::Draf) {
                $dataset->update(['status' => StatusDataset::Diajukan, 'diajukan_pada' => now()]);
                $dataset->catat('diajukan', $operator);
            }

            if ($status === StatusDataset::Disetujui) {
                $dataset->update(['status' => StatusDataset::Disetujui, 'disetujui_oleh' => $walidata->id, 'disetujui_pada' => now()]);
                $dataset->catat('disetujui', $walidata);
            }
        }
    }
}
