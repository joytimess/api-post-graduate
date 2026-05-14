<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DropoffReason extends Model
{
    protected $fillable = [
        'label', 'category', 'description'
    ];

    // Satu alasan bisa dipakai banyak enrollment
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
