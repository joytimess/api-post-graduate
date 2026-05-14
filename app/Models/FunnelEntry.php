<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelEntry extends Model
{
    protected $fillable = [
        'session_id', 'stage_id',
        'total_prospects', 'conversion_rate', 'dropoff_rate'
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:2',
        'dropoff_rate'    => 'decimal:2',
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
