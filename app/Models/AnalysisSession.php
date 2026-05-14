<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnalysisSession extends Model
{
    protected $fillable = [
        'admin_id', 'periode_name', 'start_date', 'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // Dibuat oleh admin siapa
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Satu sesi menghasilkan banyak funnel entries
    public function funnelEntries(): HasMany
    {
        return $this->hasMany(FunnelEntry::class, 'session_id');
    }

    // Satu sesi menghasilkan banyak data attrition
    public function attritionAnalyses(): HasMany
    {
        return $this->hasMany(AttritionAnalysis::class, 'session_id');
    }

    // Satu sesi menghasilkan satu data retention
    public function retentionAnalysis(): HasOne
    {
        return $this->hasOne(RetentionAnalysis::class, 'session_id');
    }

    // Satu sesi menghasilkan banyak insight
    public function insights(): HasMany
    {
        return $this->hasMany(Insight::class, 'session_id');
    }

    // Satu sesi bisa punya banyak laporan yang diekspor
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'session_id');
    }

    // Satu sesi punya banyak data traffic performance
    public function trafficPerformances(): HasMany
    {
        return $this->hasMany(TrafficPerformance::class, 'session_id');
    }
}
