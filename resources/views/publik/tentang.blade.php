@extends('layouts.app')
@section('judul', 'Tentang')

@section('konten')
    <article class="max-w-3xl space-y-6 text-slate-700">
        <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">Tentang Katalog Data OPD</h1>

        <p>
            Katalog Data OPD adalah tempat satu pintu untuk mencari dan mengunduh data yang dihasilkan perangkat daerah
            {{ config('katalog.pemda') }}. Katalog ini dikelola oleh {{ config('katalog.pengelola') }}.
        </p>

        <section>
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Bagaimana data sampai ke sini</h2>
            <ol class="list-decimal space-y-1 pl-5">
                <li>Operator OPD mengunggah data dari file Excel atau CSV.</li>
                <li>Sistem memeriksa otomatis apakah file memuat data pribadi warga.</li>
                <li>Walidata memeriksa isi dan kelengkapan dataset.</li>
                <li>Hanya dataset yang disetujui walidata yang tampil di katalog.</li>
            </ol>
        </section>

        <section>
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Perlindungan data pribadi</h2>
            <p>
                Katalog ini tidak memuat NIK, nomor KK, nama, maupun alamat warga. Data disajikan dalam bentuk rekap
                (misalnya per kecamatan atau per desa). Bila Anda menemukan data pribadi di katalog ini, mohon segera
                laporkan kepada {{ config('katalog.pengelola') }} agar dataset tersebut ditarik.
            </p>
        </section>

        <section>
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Akses untuk aplikasi (API)</h2>
            <p>Data juga tersedia dalam format JSON untuk aplikasi lain:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-mono text-sm">
                <li>GET {{ url('/api/datasets') }}</li>
                <li>GET {{ url('/api/datasets/{slug}') }}</li>
            </ul>
        </section>

        <section>
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Ketentuan pemakaian</h2>
            <p>Ketentuan pemakaian dan lisensi data sedang disusun. Sementara itu, mohon cantumkan nama OPD dan Katalog Data OPD sebagai sumber saat memakai data dari sini.</p>
        </section>
    </article>
@endsection
