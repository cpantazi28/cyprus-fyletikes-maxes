<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentQueue extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'count_finished' => 'integer',
            'seconds_per_unit' => 'integer',
            'starts_at' => 'datetime',
            'finishes_at' => 'datetime',
            'last_unit_finished_at' => 'datetime',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function getUnitNameAttribute(): string
    {
        return config("game.units.{$this->unit_type}.name", ucfirst($this->unit_type));
    }

    public function getRemainingCountAttribute(): int
    {
        return max(0, $this->count - $this->count_finished);
    }

    public function getRemainingSecondsAttribute(): int
    {
        return max(0, $this->finishes_at->diffInSeconds(now(), false) * -1);
    }
}
