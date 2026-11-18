<?php

namespace App\Services;

use App\Exceptions\FileTidakValid;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Throwable;

/**
 * Membaca file .xlsx / .csv menjadi tabel sederhana:
 * baris pertama yang terisi = nama kolom, sisanya = isi data.
 */
class PembacaTabel
{
    public const MAKS_BARIS = 20000;

    public const MAKS_KOLOM = 60;

    public const EKSTENSI = ['xlsx', 'csv'];

    /**
     * @return array{kolom: list<string>, tipe: list<string>, baris: list<list<mixed>>, peringatan: list<string>}
     *
     * @throws FileTidakValid
     */
    public function baca(string $path, string $ekstensi): array
    {
        $mentah = match (strtolower($ekstensi)) {
            'csv' => $this->bacaCsv($path),
            'xlsx' => $this->bacaXlsx($path),
            default => throw new FileTidakValid(['Format file harus .xlsx atau .csv.']),
        };

        return $this->rapikan($mentah);
    }

    /** @return list<list<mixed>> */
    private function bacaCsv(string $path): array
    {
        $isi = (string) file_get_contents($path);
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);

        if (! mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }

        $isi = str_replace(["\r\n", "\r"], "\n", $isi);

        // Excel berbahasa Indonesia biasanya menyimpan CSV dengan pemisah titik koma.
        $barisPertama = strtok(ltrim($isi, "\n"), "\n") ?: '';
        $pemisah = collect([',' => substr_count($barisPertama, ','), ';' => substr_count($barisPertama, ';'), "\t" => substr_count($barisPertama, "\t")])
            ->sortDesc()->keys()->first();

        $aliran = fopen('php://temp', 'r+');
        fwrite($aliran, $isi);
        rewind($aliran);

        $hasil = [];
        while (($baris = fgetcsv($aliran, null, $pemisah, '"', '')) !== false) {
            $hasil[] = $baris === [null] ? [] : $baris;

            if (count($hasil) > self::MAKS_BARIS + 50) {
                break;
            }
        }
        fclose($aliran);

        return $hasil;
    }

    /** @return list<list<mixed>> */
    private function bacaXlsx(string $path): array
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $sheet = $reader->load($path)->getSheet(0);
        } catch (Throwable) {
            throw new FileTidakValid(['File Excel tidak bisa dibuka. Pastikan file berformat .xlsx dan tidak rusak atau terkunci kata sandi.']);
        }

        if (count($sheet->getMergeCells()) > 0) {
            $sel = implode(', ', array_slice(array_keys($sheet->getMergeCells()), 0, 5));
            throw new FileTidakValid(["File memakai sel gabung (merge cells) di {$sel}. Hapus penggabungan sel lalu unggah ulang."]);
        }

        if ($sheet->getHighestDataRow() > self::MAKS_BARIS + 50) {
            throw new FileTidakValid(['File terlalu besar. Maksimal '.number_format(self::MAKS_BARIS, 0, ',', '.').' baris data.']);
        }

        return $sheet->toArray(null, true, false, false);
    }

    /**
     * @param  list<list<mixed>>  $mentah
     * @return array{kolom: list<string>, tipe: list<string>, baris: list<list<mixed>>, peringatan: list<string>}
     */
    private function rapikan(array $mentah): array
    {
        $baris = array_map(fn ($b) => array_map($this->normalkan(...), array_values((array) $b)), $mentah);
        $baris = array_values(array_filter($baris, fn ($b) => ! $this->kosong($b)));

        if ($baris === []) {
            throw new FileTidakValid(['File kosong. Tidak ada data yang bisa dibaca.']);
        }

        $judul = array_shift($baris);

        // Lebar tabel = kolom terakhir yang terisi, baik di judul maupun di data.
        $lebar = 0;
        foreach ([$judul, ...$baris] as $b) {
            foreach ($b as $i => $nilai) {
                if ($nilai !== null) {
                    $lebar = max($lebar, $i + 1);
                }
            }
        }

        $judul = array_pad(array_slice($judul, 0, $lebar), $lebar, null);
        $terisi = count(array_filter($judul, fn ($v) => $v !== null));

        if ($terisi === 1 && $lebar > 1) {
            throw new FileTidakValid(['Baris pertama tampaknya berisi judul tabel, bukan nama kolom. Hapus baris judul sehingga baris pertama berisi nama-nama kolom.']);
        }

        if ($lebar > self::MAKS_KOLOM) {
            throw new FileTidakValid(['Tabel memiliki terlalu banyak kolom (maksimal '.self::MAKS_KOLOM.').']);
        }

        $galat = [];
        $kolom = [];
        $sudah = [];
        foreach ($judul as $i => $nama) {
            $nama = $nama === null ? '' : trim((string) $nama);

            if ($nama === '') {
                $galat[] = 'Kolom ke-'.($i + 1).' belum punya nama di baris pertama.';

                continue;
            }

            if (mb_strlen($nama) > 100) {
                $galat[] = 'Nama kolom ke-'.($i + 1).' terlalu panjang (maksimal 100 karakter).';
            }

            $kunci = mb_strtolower($nama);
            if (isset($sudah[$kunci])) {
                $galat[] = "Nama kolom \"{$nama}\" dipakai lebih dari sekali.";
            }
            $sudah[$kunci] = true;
            $kolom[] = $nama;
        }

        if ($baris === []) {
            $galat[] = 'File hanya berisi nama kolom, belum ada baris data.';
        }

        if (count($baris) > self::MAKS_BARIS) {
            $galat[] = 'File terlalu besar. Maksimal '.number_format(self::MAKS_BARIS, 0, ',', '.').' baris data.';
        }

        if ($galat !== []) {
            throw new FileTidakValid($galat);
        }

        $baris = array_map(fn ($b) => array_pad(array_slice($b, 0, $lebar), $lebar, null), $baris);

        $tipe = [];
        for ($i = 0; $i < $lebar; $i++) {
            $nilai = array_filter(array_column($baris, $i), fn ($v) => $v !== null);
            $tipe[] = $nilai !== [] && collect($nilai)->every(fn ($v) => is_int($v) || is_float($v)) ? 'angka' : 'teks';
        }

        $peringatan = [];
        $awalTerakhir = mb_strtolower(trim((string) ($baris[array_key_last($baris)][0] ?? '')));
        if (preg_match('/^(total|jumlah|grand total|jumlah total)\b/', $awalTerakhir)) {
            $peringatan[] = 'Baris terakhir tampaknya baris total/jumlah. Sebaiknya dihapus, karena pemakai data bisa menghitung sendiri.';
        }

        return ['kolom' => $kolom, 'tipe' => $tipe, 'baris' => $baris, 'peringatan' => $peringatan];
    }

    /** Ubah nilai sel menjadi null, angka, atau teks yang sudah dirapikan. */
    private function normalkan(mixed $nilai): mixed
    {
        if ($nilai instanceof RichText) {
            $nilai = $nilai->getPlainText();
        }

        if ($nilai === null) {
            return null;
        }

        if (is_bool($nilai)) {
            return $nilai ? 'Ya' : 'Tidak';
        }

        if (is_int($nilai)) {
            return strlen((string) abs($nilai)) > 15 ? (string) $nilai : $nilai;
        }

        if (is_float($nilai)) {
            if (floor($nilai) === $nilai && abs($nilai) < 1e18) {
                $bulat = sprintf('%.0f', $nilai);

                return strlen(ltrim($bulat, '-')) > 15 ? $bulat : (int) $bulat;
            }

            return round($nilai, 10);
        }

        $teks = trim(preg_replace('/\s+/u', ' ', (string) $nilai));

        if ($teks === '') {
            return null;
        }

        // Angka biasa ("1250", "3.5") disimpan sebagai angka. Kode berawalan nol ("0812") dan
        // angka panjang (NIK, nomor rekening) tetap teks agar tidak berubah.
        if (preg_match('/^-?(0|[1-9]\d{0,14})(\.\d+)?$/', $teks)) {
            return str_contains($teks, '.') ? (float) $teks : (int) $teks;
        }

        return mb_substr($teks, 0, 1000);
    }

    private function kosong(array $baris): bool
    {
        foreach ($baris as $nilai) {
            if ($nilai !== null) {
                return false;
            }
        }

        return true;
    }
}
