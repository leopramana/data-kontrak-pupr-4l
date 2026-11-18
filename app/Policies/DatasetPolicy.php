<?php

namespace App\Policies;

use App\Enums\StatusDataset;
use App\Models\Dataset;
use App\Models\User;

class DatasetPolicy
{
    /** Melihat detail di panel: admin & walidata semua OPD, operator hanya OPD-nya sendiri. */
    public function view(User $user, Dataset $dataset): bool
    {
        return $user->isAdmin() || $user->isWalidata() || $this->milikOpd($user, $dataset);
    }

    public function create(User $user): bool
    {
        return $user->isOperator() && $user->opd_id !== null;
    }

    /** Mengubah metadata atau mengunggah ulang file. */
    public function update(User $user, Dataset $dataset): bool
    {
        return $this->milikOpd($user, $dataset) && $dataset->status->bisaDiubah();
    }

    public function ajukan(User $user, Dataset $dataset): bool
    {
        return $this->update($user, $dataset) && $dataset->jumlah_baris > 0;
    }

    public function delete(User $user, Dataset $dataset): bool
    {
        return $this->milikOpd($user, $dataset) && $dataset->status === StatusDataset::Draf;
    }

    /** Setujui atau kembalikan. */
    public function periksa(User $user, Dataset $dataset): bool
    {
        return $user->isWalidata() && $dataset->status === StatusDataset::Diajukan;
    }

    public function tarik(User $user, Dataset $dataset): bool
    {
        return $user->isWalidata() && $dataset->status === StatusDataset::Disetujui;
    }

    private function milikOpd(User $user, Dataset $dataset): bool
    {
        return $user->isOperator() && $user->opd_id !== null && $user->opd_id === $dataset->opd_id;
    }
}
