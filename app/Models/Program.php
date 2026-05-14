<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 'code', 'level', 'faculty', 'is_active'
    ];

    // Satu prodi punya banyak mahasiswa
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    // Satu prodi punya banyak record enrollment
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
