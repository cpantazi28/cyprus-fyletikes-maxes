<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'units' => 'array',
            'haul' => 'array',
            'is_cancelled' => 'boolean',
            'departs_at' => 'datetime',
            'arrives_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
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

    public function getIsCancelableAttribute(): bool
    {
        if ($this->type === 'return' || $this->is_cancelled || $this->status !== 'en_route') {
            return false;
        }

        $window = config('game.world.cancel_movement_window_seconds', 300);
        $elapsed = $this->departs_at->diffInSeconds(now());

        return $elapsed <= $window;
    }

    public function getTotalUnitsCountAttribute(): int
    {
        return array_sum($this->units ?? []);
    }

    public function getHaulTotalAttribute(): int
    {
        if (! $this->haul) {
            return 0;
        }

        return ($this->haul['wood'] ?? 0) + ($this->haul['clay'] ?? 0) + ($this->haul['iron'] ?? 0);
    }
}
