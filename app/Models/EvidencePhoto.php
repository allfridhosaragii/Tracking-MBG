<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EvidencePhoto extends Model
{
    protected $fillable = [
        'evidenceable_type',
        'evidenceable_id',
        'evidence_type',
        'uploaded_by',
        'file_path',
        'mime_type',
        'file_size',
        'captured_at',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'captured_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function evidenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
