<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Divisi extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_divisi',
        'nama_senior_manajer',
        'direktorat',
        'urutan',
    ];

    public function departemens(): HasMany
    {
        return $this->hasMany(Departemen::class);
    }
}