<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetBaris extends Model
{
    protected $table = 'dataset_baris';

    public $timestamps = false;

    protected $fillable = ['dataset_id', 'urutan', 'isi'];

    protected function casts(): array
    {
        return ['isi' => 'array'];
    }
}
