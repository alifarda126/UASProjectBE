<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Transaksi — pencatatan keuangan pemasukan & pengeluaran.
 */
class Transaksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transaksi';

    protected $fillable = [
        'organisasi_id',
        'user_id',
        'approved_by',
        'type',
        'category',
        'description',
        'amount',
        'date',
        'status',
        'notes',
        'attachment',
        'docs',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'float',
            'date'        => 'date',
            'approved_at' => 'datetime',
            'docs'        => 'array',   // JSON array of bukti files
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /* ──────────────────────────────────────
     * SCOPES (QUERY BUILDER HELPERS)
     * ────────────────────────────────────── */

    public function scopePemasukan($query)
    {
        return $query->where('type', 'pemasukan');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('type', 'pengeluaran');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /* ──────────────────────────────────────
     * METHODS
     * ────────────────────────────────────── */

    /** Setujui transaksi */
    public function approve(User $approver): bool
    {
        if ($this->status !== 'pending') return false;

        $this->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    /** Tolak transaksi */
    public function reject(User $approver, ?string $notes = null): bool
    {
        if ($this->status !== 'pending') return false;

        $this->update([
            'status'      => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes'       => $notes,
        ]);

        return true;
    }

    /** Format amount ke Rupiah */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /** URL attachment */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) return null;
        if (str_starts_with($this->attachment, 'http')) return $this->attachment;
        return asset('storage/' . $this->attachment);
    }
}
