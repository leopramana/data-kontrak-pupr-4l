<?php

namespace App\Enums;

enum StatusDataset: string
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Dikembalikan = 'dikembalikan';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Menunggu pemeriksaan',
            self::Disetujui => 'Disetujui (tayang)',
            self::Dikembalikan => 'Dikembalikan',
        };
    }

    /** Kelas warna Tailwind untuk lencana status. */
    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'bg-slate-100 text-slate-700',
            self::Diajukan => 'bg-amber-100 text-amber-800',
            self::Disetujui => 'bg-emerald-100 text-emerald-800',
            self::Dikembalikan => 'bg-rose-100 text-rose-800',
        };
    }

    /** Operator masih boleh mengubah isi dataset pada status ini. */
    public function bisaDiubah(): bool
    {
        return in_array($this, [self::Draf, self::Dikembalikan], true);
    }
}
