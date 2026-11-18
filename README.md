# Katalog Data OPD

Katalog data Organisasi Perangkat Daerah: operator OPD mengunggah dataset dari Excel/CSV,
walidata memeriksa, dan hanya dataset yang disetujui tampil ke publik dan API.

Acuan: `project-brief-katalog-data-opd.md` dan `rincian-tugas-katalog-data-opd.md`.

## Teknologi

Laravel 13 · PHP 8.3 · MySQL 8 · Blade + Tailwind CSS 4 · PhpSpreadsheet (pembaca .xlsx/.csv)

## Menjalankan di komputer lokal (Laragon)

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # lalu sesuaikan DB_* di .env
php artisan migrate --seed
php artisan serve --port=8001
```

Pada `APP_ENV=local`, seeder juga mengisi 4 dataset berlabel **[CONTOH]** (angka karangan).

### Akun awal (kata sandi = `SEED_PASSWORD`, bawaan `password`)

| Peran | Email |
|---|---|
| Admin | admin@katalog.test |
| Walidata | walidata@katalog.test |
| Operator PUPR | operator.pupr@katalog.test |
| Operator Dinkes | operator.dinkes@katalog.test |

## Test

```bash
php artisan test        # memakai database MySQL `katalog_data_opd_test`
./vendor/bin/pint       # merapikan gaya kode
```

## Alur

1. **Operator** → Unggah dataset → pratinjau → simpan sebagai draf → *Ajukan ke walidata*.
2. **Walidata** → Pemeriksaan → buka dataset → *Setujui* (wajib centang bebas data pribadi) atau *Kembalikan* dengan catatan.
   Dataset yang sudah tayang bisa *Ditarik* kapan saja.
3. **Publik** → katalog, detail, unduh CSV. **Aplikasi lain** → `GET /api/datasets`, `GET /api/datasets/{slug}`.

## Pengaman data pribadi

- `app/Services/PemindaiDataPribadi.php` menolak kolom NIK/nomor KK/KTP, nama warga, alamat rumah, RT/RW,
  serta isi berpola 16 digit. Kolom seperti `nama_kecamatan` atau `alamat_kantor` hanya diberi peringatan.
- Walidata wajib mencentang konfirmasi sebelum menyetujui.
- Unduhan CSV dibuat dari database, dengan perlindungan terhadap rumus Excel (CSV injection).

## Sebelum dipasang di server (checklist)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` = domain resmi (HTTPS)
- [ ] Ganti `SEED_PASSWORD`, jalankan `php artisan migrate --seed --force`, lalu **ganti kata sandi semua akun** dari panel admin
- [ ] Hapus dataset `[CONTOH]` bila sempat terbawa (seeder contoh hanya jalan di `local`)
- [ ] `npm run build`, `php artisan config:cache route:cache view:cache`
- [ ] Folder `storage/` dan `bootstrap/cache/` bisa ditulis web server; document root = `public/`
- [ ] Cadangan database harian (`mysqldump` terjadwal)
- [ ] Keputusan kunci akses API (saat ini terbuka, dibatasi 60 permintaan/menit per IP)
