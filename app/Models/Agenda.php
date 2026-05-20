<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Agenda — jadwal kegiatan organisasi.
 */
class Agenda extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organisasi_id',
        'user_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at'   => 'datetime',
        ];
    }

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ──────────────────────────────────────
     * SCOPES
     * ────────────────────────────────────── */

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
                     ->where('start_at', '>=', now());
    }

    public function scopeByOrganisasi($query, int $organisasiId)
    {
        return $query->where('organisasi_id', $organisasiId);
    }
}
