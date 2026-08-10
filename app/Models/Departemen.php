<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departemen extends Model
{
    protected $fillable = ['nama_departemen', 'kode', 'manajer_id'];

    public function manajer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manajer_id');
    }

    public function subdepartemens(): HasMany
    {
        return $this->hasMany(Subdepartemen::class);
    }
}