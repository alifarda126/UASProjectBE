<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasAnggota extends Model
{
    protected $table = 'kas_anggota';

    protected $fillable = [
        'organisasi_id',
        'name',
        'nim',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class);
    }
}
