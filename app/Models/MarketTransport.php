<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketTransport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'wood' => 'integer',
            'clay' => 'integer',
            'iron' => 'integer',
            'merchants' => 'integer',
            'departs_at' => 'datetime',
            'arrives_at' => 'datetime',
        ];
    }

    public function sourceVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'source_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function getRemainingSecondsAttribute(): int
    {
        return max(0, $this->arrives_at->diffInSeconds(now(), false) * -1);
    }

    public function getTotalResourcesAttribute(): int
    {
        return $this->wood + $this->clay + $this->iron;
    }
}
