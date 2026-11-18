<?php

namespace App\Services;

use App\Models\Dataset;
use Illuminate\Support\Facades\DB;

/** Mengganti seluruh kolom dan baris sebuah dataset dengan hasil baca file. */
class PenyimpanIsiDataset
{
    /**
     * @param  array{kolom: list<string>, tipe: list<string>, baris: list<list<mixed>>}  $tabel
     */
    public function simpan(Dataset $dataset, array $tabel): void
    {
        DB::transaction(function () use ($dataset, $tabel) {
            $dataset->kolom()->delete();
            $dataset->baris()->delete();

            $dataset->kolom()->createMany(array_map(
                fn ($nama, $i) => ['nama' => $nama, 'urutan' => $i, 'tipe' => $tabel['tipe'][$i] ?? 'teks'],
                $tabel['kolom'],
                array_keys($tabel['kolom']),
            ));

            foreach (array_chunk($tabel['baris'], 500, true) as $potongan) {
                $sisip = [];
                foreach ($potongan as $i => $isi) {
                    $sisip[] = [
                        'dataset_id' => $dataset->id,
                        'urutan' => $i,
                        'isi' => json_encode(array_values($isi), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
                    ];
                }
                DB::table('dataset_baris')->insert($sisip);
            }

            $dataset->update([
                'jumlah_baris' => count($tabel['baris']),
                'data_diperbarui_pada' => now(),
            ]);
        });
    }
}
