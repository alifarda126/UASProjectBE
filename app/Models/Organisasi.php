<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\KasAnggota;
use Illuminate\Support\Facades\Storage;

/**
 * Model Organisasi — entitas organisasi yang mengelola keuangan.
 */
class Organisasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'organisasi';

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'logo',
        'email',
        'phone',
        'address',
        'is_active',
        'is_suspended',
        'suspended_reason',
        'suspended_at',
        'created_by',
        'dues_interval',
        'dues_amount',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'dues_interval' => 'integer',
        'dues_amount' => 'decimal:2',
    ];

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    /** Anggota organisasi ini */
    public function users()
    {
        return $this->belongsToMany(User::class, 'anggota_organisasi')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    /** Anggota melalui pivot model */
    public function anggota()
    {
        return $this->hasMany(AnggotaOrganisasi::class);
    }

    /** Semua transaksi organisasi */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    /** Pengajuan banding dari organisasi ini */
    public function bandings()
    {
        return $this->hasMany(BandingOrganisasi::class);
    }

    /** Semua agenda organisasi */
    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }

    /** Anggota kas organisasi (dari form tambah anggota kas) */
    public function kasAnggota()
    {
        return $this->hasMany(KasAnggota::class, 'organisasi_id');
    }

    /** User pembuat organisasi */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ──────────────────────────────────────
     * METHODS KALKULASI KEUANGAN
     * ────────────────────────────────────── */

    /** Total pemasukan (semua transaksi) */
    public function getTotalPemasukan(): float
    {
        return (float) $this->transaksi()
            ->where('type', 'pemasukan')
            ->sum('amount');
    }

    /** Total pengeluaran (semua transaksi) */
    public function getTotalPengeluaran(): float
    {
        return (float) $this->transaksi()
            ->where('type', 'pengeluaran')
            ->sum('amount');
    }

    /** Saldo = pemasukan - pengeluaran */
    public function getSaldo(): float
    {
        return $this->getTotalPemasukan() - $this->getTotalPengeluaran();
    }

    /** URL logo atau null */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;
        // URL eksternal — langsung dikembalikan
        if (str_starts_with($this->logo, 'http')) return $this->logo;
        // Path file lokal/S3 — generate URL via Storage facade
        $url = Storage::url($this->logo);
        return str_starts_with($url, '/') ? asset($url) : $url;
    }
}
