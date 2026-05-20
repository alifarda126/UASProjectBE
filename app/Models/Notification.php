<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Notification — notifikasi per user.
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'icon',
        'link',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    /* ──────────────────────────────────────
     * RELASI
     * ────────────────────────────────────── */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ──────────────────────────────────────
     * SCOPES
     * ────────────────────────────────────── */

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /* ──────────────────────────────────────
     * METHODS
     * ────────────────────────────────────── */

    /** Tandai notifikasi sebagai sudah dibaca */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}
