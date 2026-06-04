<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model BandingOrganisasi — pengajuan banding dari organisasi yang tersuspend.
 */
class BandingOrganisasi extends Model
{
    use HasFactory;

    protected $table = 'banding_organisasi';

    protected $fillable = [
        'organisasi_id',
        'user_id',
        'message',
        'evidence_path',
        'status',
        'admin_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ──────────────────────────────────────
     * SCOPES
     * ────────────────────────────────────── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** URL bukti foto — mendukung URL lengkap (S3) dan path relatif (legacy) */
    public function getEvidenceUrlAttribute(): ?string
    {
        if (!$this->evidence_path) return null;
        // URL lengkap (S3/Supabase atau URL eksternal) — langsung dikembalikan
        if (str_starts_with($this->evidence_path, 'http')) return $this->evidence_path;
        // Path relatif (legacy local storage) — generate URL via Storage facade
        $disk = config('filesystems.default');
        $url = \Illuminate\Support\Facades\Storage::disk($disk)->url($this->evidence_path);
        return str_starts_with($url, '/') ? asset($url) : $url;
    }
}
