<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetKolom extends Model
{
    protected $table = 'dataset_kolom';

    public $timestamps = false;

    protected $fillable = ['dataset_id', 'nama', 'urutan', 'tipe'];
}
