<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageSupport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'units' => 'array',
        ];
    }

    public function homeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'home_village_id');
    }

    public function stationedVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'stationed_village_id');
    }

    public function getTotalUnitsCountAttribute(): int
    {
        return array_sum($this->units ?? []);
    }
}
