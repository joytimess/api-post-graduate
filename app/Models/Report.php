<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'session_id', 'name', 'path_file', 'file_type', 'exported_at'
    ];

    protected $casts = [
        'exported_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AnalysisSession::class, 'session_id');
    }
}
