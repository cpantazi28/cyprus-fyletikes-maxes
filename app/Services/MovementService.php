<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Village;
use App\Models\VillageUnit;
use Exception;

class MovementService
{
    /**
     * Calculate distance and travel duration in seconds between two villages for a given army composition.
     */
    public function calculateTravelDuration(Village $source, Village $target, array $units): int
    {
        $distance = $source->distanceTo($target);
        if ($distance <= 0) {
            return 10; // Minimum travel time
        }

        $slowestSpeed = 0;
        $unitConfigs = config('game.units', []);

        foreach ($units as $unitKey => $count) {
            if ($count > 0 && isset($unitConfigs[$unitKey])) {
                $unitSpeed = $unitConfigs[$unitKey]['speed']; // Minutes per field
                if ($unitSpeed > $slowestSpeed) {
                    $slowestSpeed = $unitSpeed;
                }
            }
        }

        if ($slowestSpeed === 0) {
            $slowestSpeed = 18; // Default spear speed
        }

        $worldSpeed = config('game.world.speed', 1.0);
        $unitSpeedMultiplier = config('game.world.unit_speed', 1.0);

        $totalMinutes = ($distance * $slowestSpeed) / ($worldSpeed * $unitSpeedMultiplier);

        return max(5, (int) round($totalMinutes * 60));
    }

    /**
     * Dispatch troop movement (Attack, Support).
     */
    public function dispatchMovement(
        Village $source,
        Village $target,
        string $type,
        array $units,
        ?string $catapultTarget = null
    ): Movement {
        if ($source->place < 1) {
            throw new Exception('Rally point is required to issue commands.');
        }

        if ($source->id === $target->id) {
            throw new Exception('Cannot send troops to the same village.');
        }

        $totalUnits = 0;
        $sanitizedUnits = [];
        $sourceUnits = $source->units ?: VillageUnit::create(['village_id' => $source->id]);

        $unitConfigs = config('game.units', []);
        foreach ($unitConfigs as $unitKey => $config) {
            $count = (int) ($units[$unitKey] ?? 0);
            if ($count > 0) {
                $available = $sourceUnits->{$unitKey} ?? 0;
                if ($count > $available) {
                    throw new Exception("Not enough {$config['name']} available ({$available} in village).");
                }
                $sanitizedUnits[$unitKey] = $count;
                $totalUnits += $count;
            } else {
                $sanitizedUnits[$unitKey] = 0;
            }
        }

        if ($totalUnits <= 0) {
            throw new Exception('You must select at least one unit to send.');
        }

        // Check beginner protection for attacks on other players
        if ($type === 'attack' && $target->user_id && $target->user_id !== $source->user_id) {
            $targetUser = $target->user;
            if ($targetUser) {
                $protectionHours = config('game.world.beginner_protection_hours', 72);
                $protectionEnds = $targetUser->created_at->copy()->addHours($protectionHours);
                if (now()->isBefore($protectionEnds)) {
                    throw new Exception("This player is under beginner protection until {$protectionEnds->toDateTimeString()}.");
                }
            }
        }

        // Deduct troops from source village
        foreach ($sanitizedUnits as $unitKey => $count) {
            if ($count > 0) {
                $sourceUnits->{$unitKey} -= $count;
            }
        }
        $sourceUnits->save();

        $durationSeconds = $this->calculateTravelDuration($source, $target, $sanitizedUnits);
        $departsAt = now();
        $arrivesAt = $departsAt->copy()->addSeconds($durationSeconds);

        return Movement::create([
            'user_id' => $source->user_id,
            'target_user_id' => $target->user_id,
            'source_village_id' => $source->id,
            'target_village_id' => $target->id,
            'type' => $type,
            'units' => $sanitizedUnits,
            'haul' => null,
            'catapult_target' => ($type === 'attack' && ($sanitizedUnits['catapult'] ?? 0) > 0) ? $catapultTarget : null,
            'departs_at' => $departsAt,
            'arrives_at' => $arrivesAt,
            'status' => 'en_route',
        ]);
    }

    /**
     * Cancel an outgoing attack or support movement within the cancellation window.
     */
    public function cancelMovement(Movement $movement): Movement
    {
        if (! $movement->is_cancelable) {
            throw new Exception('This command can no longer be cancelled.');
        }

        $elapsedSeconds = $movement->departs_at->diffInSeconds(now());
        $movement->status = 'cancelled';
        $movement->is_cancelled = true;
        $movement->save();

        // Create return movement
        return Movement::create([
            'user_id' => $movement->user_id,
            'target_user_id' => null,
            'source_village_id' => $movement->target_village_id,
            'target_village_id' => $movement->source_village_id,
            'type' => 'return',
            'units' => $movement->units,
            'haul' => null,
            'catapult_target' => null,
            'departs_at' => now(),
            'arrives_at' => now()->addSeconds($elapsedSeconds),
            'status' => 'en_route',
        ]);
    }
}
