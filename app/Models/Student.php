<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nim', 'name', 'email', 'program_id',
        'status', 'enrolled_at', 'graduated_at'
    ];

    protected $casts = [
        'enrolled_at'   => 'date',
        'graduated_at'  => 'date',
    ];

    // Mahasiswa ini di prodi mana
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    // Satu mahasiswa punya banyak record enrollment (per tahap)
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    // Shortcut: tahap-tahap yang dilalui mahasiswa ini
    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(FunnelStage::class, 'enrollments')
                    ->withPivot('status', 'enrolled_date', 'completed_date')
                    ->withTimestamps();
    }
}
