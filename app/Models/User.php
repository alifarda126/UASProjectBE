<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

/**
 * Model User — entitas utama pengguna aplikasi MoneFlo.
 * Mendukung autentikasi via email/password dan OAuth (Google).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'provider',
        'provider_id',
        'provider_token',
        'role',
        'is_active',
        'last_login_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'provider_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    /** User tergabung dalam banyak organisasi melalui tabel pivot */
    public function organisasi()
    {
        return $this->belongsToMany(Organisasi::class, 'anggota_organisasi')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    /** Transaksi yang dibuat user ini */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    /** Transaksi yang di-approve user ini */
    public function approvedTransaksi()
    {
        return $this->hasMany(Transaksi::class, 'approved_by');
    }

    /** Agenda yang dibuat user ini */
    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }

    /** Notifikasi user ini */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /** Keanggotaan organisasi (pivot model) */
    public function keanggotaan()
    {
        return $this->hasMany(AnggotaOrganisasi::class);
    }

    /* ──────────────────────────────────────
     * METHODS
     * ────────────────────────────────────── */

    /** Cek apakah user adalah admin */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Cek apakah user aktif */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /** Update waktu login terakhir */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /** Dapatkan URL avatar atau null */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) return null;
        // URL eksternal (Google OAuth, dsb) — langsung dikembalikan
        if (str_starts_with($this->avatar, 'http')) return $this->avatar;
        // Path file lokal/S3 — generate URL via Storage facade
        $url = Storage::url($this->avatar);
        return str_starts_with($url, '/') ? asset($url) : $url;
    }

    /** Inisial nama untuk placeholder avatar */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper($words[0][0] . $words[1][0]);
        }
        return strtoupper(substr($this->name, 0, 2));
    }
}
