<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetRiwayat extends Model
{
    protected $table = 'dataset_riwayat';

    public const UPDATED_AT = null;

    protected $fillable = ['dataset_id', 'user_id', 'aksi', 'catatan'];

    public const LABEL = [
        'dibuat' => 'Dataset dibuat',
        'diunggah_ulang' => 'File diunggah ulang',
        'metadata_diubah' => 'Metadata diubah',
        'diajukan' => 'Diajukan ke walidata',
        'disetujui' => 'Disetujui dan tayang',
        'dikembalikan' => 'Dikembalikan ke operator',
        'ditarik' => 'Ditarik dari publik',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::LABEL[$this->aksi] ?? $this->aksi;
    }
}
