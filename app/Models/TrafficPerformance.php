<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficPerformance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'session_id', 'source_id',
        'impressions', 'clicks', 'leads', 'enrollments', 'conversion_rate'
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalysisSession::class, 'session_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(TrafficSource::class, 'source_id');
    }
}
