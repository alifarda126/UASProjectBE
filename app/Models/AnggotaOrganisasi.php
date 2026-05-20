<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model AnggotaOrganisasi — pivot model keanggotaan.
 * Menyimpan relasi user ↔ organisasi beserta role anggota.
 */
class AnggotaOrganisasi extends Model
{
    protected $table = 'anggota_organisasi';

    protected $fillable = [
        'user_id',
        'organisasi_id',
        'role',
        'joined_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class);
    }

    /* ──────────────────────────────────────
     * METHODS
     * ────────────────────────────────────── */

    public function isKetua(): bool
    {
        return $this->role === 'ketua';
    }

    public function isBendahara(): bool
    {
        return $this->role === 'bendahara';
    }

    public function isSekretaris(): bool
    {
        return $this->role === 'sekretaris';
    }

    public function canApproveTransaksi(): bool
    {
        return in_array($this->role, ['ketua', 'bendahara']);
    }
}
