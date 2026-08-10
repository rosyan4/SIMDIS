<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispensasi extends Model
{
    protected $fillable = [
        'nomor_dispensasi', 'user_id', 'tanggal_dispensasi',
        'jam_mulai', 'jam_selesai', 'alasan', 'bukti_pendukung',
        'status', 'approved_by', 'approved_at', 'catatan_persetujuan',
        'escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_dispensasi' => 'date',
            'approved_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}