<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insight extends Model
{
    protected $fillable = [
        'session_id', 'insight_type', 'description', 'recommendation'
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalysisSession::class, 'session_id');
    }

    // Scope per tipe — berguna untuk query di dashboard
    public function scopeOfType($query, string $type)
    {
        return $query->where('insight_type', $type);
    }
}
