<?php

namespace App\Models;

use App\Enums\StatusDataset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Dataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'opd_id', 'judul', 'slug', 'deskripsi', 'periode', 'satuan', 'frekuensi', 'sumber',
        'status', 'catatan_walidata', 'hasil_pemindaian', 'nama_file_asli', 'jumlah_baris',
        'dibuat_oleh', 'diajukan_pada', 'disetujui_oleh', 'disetujui_pada', 'data_diperbarui_pada',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusDataset::class,
            'hasil_pemindaian' => 'array',
            'diajukan_pada' => 'datetime',
            'disetujui_pada' => 'datetime',
            'data_diperbarui_pada' => 'datetime',
        ];
    }

    public const FREKUENSI = ['Bulanan', 'Triwulanan', 'Semesteran', 'Tahunan', 'Tidak tentu'];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function kolom(): HasMany
    {
        return $this->hasMany(DatasetKolom::class)->orderBy('urutan');
    }

    public function baris(): HasMany
    {
        return $this->hasMany(DatasetBaris::class)->orderBy('urutan');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(DatasetRiwayat::class)->latest('id');
    }

    /** Hanya dataset yang sudah disetujui walidata yang boleh dilihat publik. */
    public function scopeTayang(Builder $query): Builder
    {
        return $query->where('status', StatusDataset::Disetujui->value);
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return $query;
        }

        $pola = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $kata).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('judul', 'like', $pola)
            ->orWhere('deskripsi', 'like', $pola));
    }

    public static function buatSlug(string $judul, ?int $kecualiId = null): string
    {
        $dasar = Str::slug($judul) ?: 'dataset';
        $slug = $dasar;
        $i = 2;

        while (static::where('slug', $slug)->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))->exists()) {
            $slug = $dasar.'-'.$i++;
        }

        return $slug;
    }

    public function catat(string $aksi, ?User $user, ?string $catatan = null): void
    {
        $this->riwayat()->create([
            'user_id' => $user?->id,
            'aksi' => $aksi,
            'catatan' => $catatan,
        ]);
    }
}
