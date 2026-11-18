<?php

namespace App\Exceptions;

use Exception;

/** File unggahan tidak bisa dibaca sebagai tabel yang benar. Berisi daftar pesan untuk operator. */
class FileTidakValid extends Exception
{
    /** @param  list<string>  $pesan */
    public function __construct(public readonly array $pesan)
    {
        parent::__construct(implode(' ', $pesan));
    }
}
