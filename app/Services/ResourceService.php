<?php

namespace App\Services;

use App\Models\Village;

class ResourceService
{
    /**
     * Accrue resources and recover loyalty based on elapsed time.
     */
    public function accrueResources(Village $village): Village
    {
        $now = now();
        $lastUpdate = $village->last_resource_update ?? $village->created_at ?? $now;
        $seconds = $lastUpdate->diffInSeconds($now);

        if ($seconds > 0) {
            $woodPerHour = $village->wood_production_per_hour;
            $clayPerHour = $village->clay_production_per_hour;
            $ironPerHour = $village->iron_production_per_hour;

            $woodGained = ($woodPerHour / 3600) * $seconds;
            $clayGained = ($clayPerHour / 3600) * $seconds;
            $ironGained = ($ironPerHour / 3600) * $seconds;

            $capacity = $village->storage_capacity;

            $village->wood = min($capacity, (float) $village->wood + $woodGained);
            $village->clay = min($capacity, (float) $village->clay + $clayGained);
            $village->iron = min($capacity, (float) $village->iron + $ironGained);
            $village->last_resource_update = $now;
        }

        // Loyalty recovery
        if ($village->loyalty < 100) {
            $lastLoyaltyUpdate = $village->last_loyalty_update ?? $village->created_at ?? $now;
            $loyaltySeconds = $lastLoyaltyUpdate->diffInSeconds($now);

            if ($loyaltySeconds > 0) {
                $speed = config('game.world.speed', 1.0);
                $recoveryPerHour = config('game.world.loyalty_recovery_per_hour', 1.0) * $speed;
                $loyaltyGained = ($recoveryPerHour / 3600) * $loyaltySeconds;

                $village->loyalty = min(100.0, (float) $village->loyalty + $loyaltyGained);
                $village->last_loyalty_update = $now;
            }
        } else {
            $village->last_loyalty_update = $now;
        }

        $village->save();

        return $village;
    }

    /**
     * Check if the village has sufficient resources.
     */
    public function canAfford(Village $village, int $wood, int $clay, int $iron): bool
    {
        return $village->wood >= $wood && $village->clay >= $clay && $village->iron >= $iron;
    }

    /**
     * Deduct resources from the village.
     */
    public function deductResources(Village $village, int $wood, int $clay, int $iron): bool
    {
        if (! $this->canAfford($village, $wood, $clay, $iron)) {
            return false;
        }

        $village->wood = max(0, (float) $village->wood - $wood);
        $village->clay = max(0, (float) $village->clay - $clay);
        $village->iron = max(0, (float) $village->iron - $iron);
        $village->last_resource_update = now();
        $village->save();

        return true;
    }

    /**
     * Add resources to the village respecting storage capacity.
     */
    public function addResources(Village $village, int $wood, int $clay, int $iron): array
    {
        $capacity = $village->storage_capacity;

        $woodAdded = min($wood, max(0, $capacity - (int) $village->wood));
        $clayAdded = min($clay, max(0, $capacity - (int) $village->clay));
        $ironAdded = min($iron, max(0, $capacity - (int) $village->iron));

        $village->wood = min($capacity, (float) $village->wood + $woodAdded);
        $village->clay = min($capacity, (float) $village->clay + $clayAdded);
        $village->iron = min($capacity, (float) $village->iron + $ironAdded);
        $village->last_resource_update = now();
        $village->save();

        return [
            'wood' => $woodAdded,
            'clay' => $clayAdded,
            'iron' => $ironAdded,
        ];
    }
}
