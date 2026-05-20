<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramAnggaran extends Model
{
    protected $table = 'program_anggaran';

    protected $fillable = [
        'organisasi_id',
        'name',
        'progress',
    ];

    protected $casts = [
        'progress' => 'integer',
    ];

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class);
    }
}
