<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunnelStage extends Model
{
    use HasFactory;
    
    protected $fillable = [
            'name', 'order', 'description'
        ];

        // Satu tahap punya banyak record enrollment
        public function enrollments(): HasMany
        {
            return $this->hasMany(Enrollment::class, 'stage_id');
        }

        // Shortcut: mahasiswa mana saja yang pernah di tahap ini
        public function students(): BelongsToMany
        {
            return $this->belongsToMany(Student::class, 'enrollments')
                        ->withPivot('status', 'enrolled_date', 'completed_date');
        }
}
