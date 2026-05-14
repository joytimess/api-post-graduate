<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id', 'program_id', 'stage_id',
        'status', 'dropoff_reason_id',
        'enrolled_date', 'completed_date', 'notes'
    ];

    protected $casts = [
        'enrolled_date'   => 'date',
        'completed_date'  => 'date',
    ];

    // Enrollment ini milik mahasiswa siapa
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Di prodi mana
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    // Di tahap funnel mana
    public function stage(): BelongsTo
    {
        return $this->belongsTo(FunnelStage::class, 'stage_id');
    }

    // Alasan keluar (nullable — tidak semua enrollment punya ini)
    public function dropoffReason(): BelongsTo
    {
        return $this->belongsTo(DropoffReason::class);
    }

    // Scope helper untuk analisis — langsung pakai di query
    public function scopeDropped($query)
    {
        return $query->whereIn('status', ['failed', 'dropout']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeByStage($query, $stageId)
    {
        return $query->where('stage_id', $stageId);
    }
}
