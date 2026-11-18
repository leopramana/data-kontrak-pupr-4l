@extends('layouts.app')
@php($baru = ! $dataset->exists)
@section('judul', $baru ? 'Unggah dataset' : 'Ubah dataset')

@section('konten')
    @php($sembunyikanGalatUmum = true)
    <div class="max-w-3xl">
        <h1 class="text-2xl font-semibold">{{ $baru ? 'Unggah dataset baru' : 'Ubah dataset' }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            Isi keterangan dataset, lalu pilih file. Anda akan melihat pratinjau tabel sebelum data disimpan.
        </p>

        @error('file')
            <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                <p class="font-medium">File belum bisa diterima:</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach($errors->get('file') as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @enderror

        <form method="POST" enctype="multipart/form-data"
              action="{{ $baru ? route('dataset.store') : route('dataset.update', $dataset) }}" class="kartu mt-6 space-y-5">
            @csrf
            @unless($baru) @method('PUT') @endunless

            <div>
                <label for="judul" class="label">Judul dataset <span class="text-rose-600">*</span></label>
                <input id="judul" name="judul" value="{{ old('judul', $dataset->judul) }}" required maxlength="200" class="input"
                       placeholder="Contoh: Jumlah Posyandu per Kecamatan" @error('judul') aria-invalid="true" @enderror>
                @error('judul')<p class="galat">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="deskripsi" class="label">Deskripsi <span class="text-rose-600">*</span></label>
                <textarea id="deskripsi" name="deskripsi" rows="4" required maxlength="2000" class="input"
                          placeholder="Jelaskan isi data, cakupan wilayah, dan cara pengumpulannya." @error('deskripsi') aria-invalid="true" @enderror>{{ old('deskripsi', $dataset->deskripsi) }}</textarea>
                @error('deskripsi')<p class="galat">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="periode" class="label">Periode</label>
                    <input id="periode" name="periode" value="{{ old('periode', $dataset->periode) }}" maxlength="100" class="input" placeholder="Contoh: 2025">
                    @error('periode')<p class="galat">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="satuan" class="label">Satuan</label>
                    <input id="satuan" name="satuan" value="{{ old('satuan', $dataset->satuan) }}" maxlength="100" class="input" placeholder="Contoh: unit, orang, km">
                    @error('satuan')<p class="galat">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="frekuensi" class="label">Frekuensi pembaruan</label>
                    <select id="frekuensi" name="frekuensi" class="input">
                        <option value="">Pilih…</option>
                        @foreach(\App\Models\Dataset::FREKUENSI as $f)
                            <option @selected(old('frekuensi', $dataset->frekuensi) === $f)>{{ $f }}</option>
                        @endforeach
                    </select>
                    @error('frekuensi')<p class="galat">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sumber" class="label">Sumber data</label>
                    <input id="sumber" name="sumber" value="{{ old('sumber', $dataset->sumber) }}" maxlength="255" class="input" placeholder="Contoh: Laporan bulanan puskesmas">
                    @error('sumber')<p class="galat">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="file" class="label">
                    File data (.xlsx atau .csv, maks. 10 MB)
                    @if($baru)<span class="text-rose-600">*</span>@endif
                </label>
                <input id="file" name="file" type="file" accept=".xlsx,.csv" @if($baru) required @endif
                       class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-medium file:text-brand-800 hover:file:bg-brand-100">
                <p class="bantuan">
                    @unless($baru) Kosongkan bila hanya mengubah keterangan. File baru akan <strong>mengganti seluruh isi data</strong>. @endunless
                    Baris pertama berisi nama kolom, tanpa judul tabel, tanpa sel gabung.
                    <strong>Dilarang memuat NIK, nomor KK, nama, atau alamat warga.</strong>
                </p>
            </div>

            <div class="flex gap-2 border-t border-slate-100 pt-5">
                <button class="btn btn-utama">{{ $baru ? 'Lanjut ke pratinjau' : 'Simpan' }}</button>
                <a href="{{ $baru ? route('dataset.index') : route('dataset.show', $dataset) }}" class="btn">Batal</a>
            </div>
        </form>
    </div>
@endsection
