<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opd extends Model
{
    use HasFactory;

    protected $table = 'opd';

    protected $fillable = ['nama', 'singkatan'];

    public function datasets(): HasMany
    {
        return $this->hasMany(Dataset::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
