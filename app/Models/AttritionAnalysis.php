<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttritionAnalysis extends Model
{
    protected $fillable = [
        'session_id', 'stage_id',
        'risk_level', 'attrition_rate', 'dropoff_reason'
    ];

    protected $casts = [
        'attrition_rate' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalysisSession::class, 'session_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(FunnelStage::class, 'stage_id');
    }
}
