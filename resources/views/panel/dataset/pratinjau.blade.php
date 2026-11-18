@extends('layouts.app')
@section('judul', 'Pratinjau unggahan')

@section('konten')
    <h1 class="text-2xl font-semibold">Pratinjau: {{ $unggahan['meta']['judul'] }}</h1>
    <p class="mt-1 text-sm text-slate-600">
        File <strong>{{ $unggahan['nama_file'] }}</strong> terbaca
        <strong>{{ count($tabel['kolom']) }} kolom</strong> dan
        <strong>{{ number_format(count($tabel['baris']), 0, ',', '.') }} baris data</strong>.
        Periksa apakah tabel sudah benar sebelum menyimpan.
        @if($dataset)
            <br><span class="text-amber-700">Isi data lama dari dataset ini akan diganti seluruhnya.</span>
        @endif
    </p>

    @if($peringatan)
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            <p class="font-medium">Perlu diperhatikan (tidak menghalangi penyimpanan, tetapi akan dilihat walidata):</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach($peringatan as $p)<li>{{ $p }}</li>@endforeach
            </ul>
        </div>
    @endif

    <h2 class="mb-2 mt-6 text-sm font-medium text-slate-600">
        {{ count($tabel['baris']) > 20 ? '20 baris pertama' : 'Seluruh baris' }}
    </h2>
    @include('partials.tabel-data', [
        'namaKolom' => $tabel['kolom'],
        'tipe' => $tabel['tipe'],
        'isiBaris' => array_slice($tabel['baris'], 0, 20),
        'nomorAwal' => 1,
    ])

    <div class="mt-6 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('pratinjau.simpan') }}">
            @csrf
            <button class="btn btn-utama">Simpan sebagai draf</button>
        </form>
        <form method="POST" action="{{ route('pratinjau.batal') }}">
            @csrf
            <button class="btn">Batal, pilih file lain</button>
        </form>
    </div>
@endsection
