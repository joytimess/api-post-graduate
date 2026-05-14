<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficSource extends Model
{
    protected $fillable = [
        'name', 'category', 'cost_per_lead'
    ];

    protected $casts = [
        'cost_per_lead' => 'decimal:2',
    ];

    // Satu sumber traffic punya banyak data performa
    public function performances(): HasMany
    {
        return $this->hasMany(TrafficPerformance::class, 'source_id');
    }
}
