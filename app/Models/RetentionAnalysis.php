<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionAnalysis extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'session_id',
        'retention_rate', 'active_students', 'inactive_students'
    ];

    protected $casts = [
        'retention_rate' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalysisSession::class, 'session_id');
    }
}
