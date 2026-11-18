<?php

namespace Database\Factories;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dataset>
 */
class DatasetFactory extends Factory
{
    public function definition(): array
    {
        $judul = 'Dataset '.fake()->unique()->words(3, true);

        return [
            'opd_id' => Opd::factory(),
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.Str::random(5),
            'deskripsi' => fake()->sentence(),
            'status' => StatusDataset::Draf,
            'dibuat_oleh' => User::factory(),
            'jumlah_baris' => 0,
        ];
    }

    public function status(StatusDataset $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function disetujui(): static
    {
        return $this->state([
            'status' => StatusDataset::Disetujui,
            'disetujui_pada' => now(),
            'data_diperbarui_pada' => now(),
        ]);
    }

    /** Isi dataset dengan kolom dan baris sederhana. */
    public function denganIsi(array $kolom = ['kecamatan', 'jumlah'], array $baris = [['Pendopo', 10], ['Saling', 5]]): static
    {
        return $this->afterCreating(function (Dataset $dataset) use ($kolom, $baris) {
            foreach ($kolom as $i => $nama) {
                $dataset->kolom()->create(['nama' => $nama, 'urutan' => $i, 'tipe' => 'teks']);
            }
            foreach ($baris as $i => $isi) {
                $dataset->baris()->create(['urutan' => $i, 'isi' => $isi]);
            }
            $dataset->update(['jumlah_baris' => count($baris)]);
        });
    }
}
