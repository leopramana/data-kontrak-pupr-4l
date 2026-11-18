<?php

namespace App\Services;

/**
 * Lapis pertama pengaman data pribadi (K4).
 *
 * - "tolak"      : file tidak boleh disimpan sama sekali.
 * - "peringatan" : file boleh disimpan, tetapi walidata wajib memeriksa kolom tersebut.
 *
 * Pemindai ini tidak sempurna. Keputusan akhir tetap di tangan walidata.
 */
class PemindaiDataPribadi
{
    /** Kata yang menandakan kolom berisi orang perorangan. */
    private const KATA_ORANG = [
        'lengkap', 'warga', 'penduduk', 'pasien', 'penerima', 'pemilik', 'ibu', 'ayah', 'anak', 'balita',
        'bayi', 'siswa', 'murid', 'suami', 'istri', 'responden', 'peserta', 'pemohon', 'pelapor', 'korban',
        'tersangka', 'orang', 'pribadi', 'individu', 'kepala', 'keluarga', 'kk', 'krt', 'ortu', 'wali',
    ];

    /** Kata yang menandakan alamat lembaga/fasilitas, bukan rumah warga. */
    private const KATA_LEMBAGA = [
        'kantor', 'opd', 'instansi', 'dinas', 'puskesmas', 'pustu', 'rs', 'sakit', 'sekolah', 'fasilitas',
        'faskes', 'unit', 'lokasi', 'proyek', 'kegiatan', 'pasar', 'posyandu', 'klinik', 'balai',
    ];

    private const KATA_KONTAK = ['hp', 'telepon', 'telp', 'tlp', 'wa', 'whatsapp', 'email', 'lahir', 'tgl lahir'];

    /**
     * @param  list<string>  $kolom
     * @param  list<list<mixed>>  $baris
     * @return array{tolak: list<string>, peringatan: list<string>}
     */
    public function pindai(array $kolom, array $baris): array
    {
        $tolak = [];
        $peringatan = [];

        foreach ($kolom as $i => $nama) {
            $kata = $this->kata($nama);
            $ada = fn (array $daftar) => array_intersect($kata, $daftar) !== [];

            // "jumlah_kk" atau "jumlah kartu keluarga" adalah data agregat dan boleh;
            // yang ditolak adalah NIK dan *nomor* KK.
            $nomor = $ada(['no', 'nomor', 'nomer', 'nmr']);
            if ($ada(['nik', 'nkk', 'ktp']) || $kata === ['kk'] || ($nomor && in_array('kk', $kata, true))
                || ($nomor && $this->frasa($kata, ['kartu', 'keluarga'])) || $this->frasa($kata, ['induk', 'kependudukan'])) {
                $tolak[] = "Kolom \"{$nama}\" tampaknya berisi NIK/nomor KK. Hapus kolom ini.";
            } elseif (in_array('alamat', $kata, true)) {
                if ($ada(self::KATA_LEMBAGA)) {
                    $peringatan[] = "Kolom \"{$nama}\": pastikan isinya alamat kantor/fasilitas, bukan alamat rumah warga.";
                } else {
                    $tolak[] = "Kolom \"{$nama}\" tampaknya berisi alamat warga. Hapus kolom ini, atau sajikan data per kecamatan/desa.";
                }
            } elseif (in_array('rtrw', $kata, true) || (in_array('rt', $kata, true) && in_array('rw', $kata, true))) {
                $tolak[] = "Kolom \"{$nama}\" berisi RT/RW, yang merupakan bagian alamat warga. Hapus kolom ini.";
            } elseif (in_array('nama', $kata, true)) {
                if ($kata === ['nama'] || $ada(self::KATA_ORANG)) {
                    $tolak[] = "Kolom \"{$nama}\" tampaknya berisi nama warga. Hapus kolom ini, atau sajikan data dalam bentuk jumlah.";
                } else {
                    $peringatan[] = "Kolom \"{$nama}\": pastikan isinya nama tempat/lembaga, bukan nama warga.";
                }
            } elseif ($ada(self::KATA_KONTAK) || $this->frasa($kata, ['tanggal', 'lahir'])) {
                $peringatan[] = "Kolom \"{$nama}\" mungkin berisi data pribadi (kontak atau tanggal lahir). Periksa kembali.";
            }

            $temuan = $this->cariPolaNik(array_column($baris, $i));
            if ($temuan !== []) {
                $tolak[] = "Kolom \"{$nama}\" berisi angka 16 digit (pola NIK/KK) di baris data ke-".implode(', ', $temuan).'.';
            }
        }

        return ['tolak' => array_values(array_unique($tolak)), 'peringatan' => array_values(array_unique($peringatan))];
    }

    /** @return list<string> */
    private function kata(string $nama): array
    {
        $teks = mb_strtolower($nama);
        $teks = str_replace(['rt/rw', 'rt-rw', 'rt_rw'], ' rtrw ', $teks);
        $teks = preg_replace('/[^a-z0-9]+/', ' ', $teks);

        return array_values(array_filter(explode(' ', trim($teks)), fn ($k) => $k !== ''));
    }

    private function frasa(array $kata, array $urutan): bool
    {
        return str_contains(' '.implode(' ', $kata).' ', ' '.implode(' ', $urutan).' ');
    }

    /** @return list<int> nomor baris data (mulai 1), paling banyak 5 */
    private function cariPolaNik(array $nilai): array
    {
        $temuan = [];

        foreach ($nilai as $i => $v) {
            if ($v === null || is_float($v)) {
                continue;
            }

            $angka = preg_replace('/[\s.\-\']/', '', (string) $v);

            if (preg_match('/^\d{16}$/', $angka)) {
                $temuan[] = $i + 1;

                if (count($temuan) >= 5) {
                    break;
                }
            }
        }

        return $temuan;
    }
}
