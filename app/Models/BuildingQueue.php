<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingQueue extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'build_time_seconds' => 'integer',
            'starts_at' => 'datetime',
            'finishes_at' => 'datetime',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function getBuildingNameAttribute(): string
    {
        return config("game.buildings.{$this->building}.name", ucfirst($this->building));
    }

    public function getRemainingSecondsAttribute(): int
    {
        return max(0, $this->finishes_at->diffInSeconds(now(), false) * -1);
    }
}
