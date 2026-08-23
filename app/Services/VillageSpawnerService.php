<?php

namespace App\Services;

use App\Models\User;
use App\Models\Village;
use App\Models\VillageUnit;

class VillageSpawnerService
{
    /**
     * Spawn a starting village for a player in the world.
     */
    public function spawnStartingVillage(User $user, string $direction = 'random'): Village
    {
        $coords = $this->findUnoccupiedCoordinate($direction);

        $village = Village::create([
            'user_id' => $user->id,
            'name' => "{$user->name}'s village",
            'x' => $coords['x'],
            'y' => $coords['y'],
            'points' => 26,
            'loyalty' => 100.0,
            'wood' => 500.0,
            'clay' => 500.0,
            'iron' => 400.0,
            'last_resource_update' => now(),
            'last_loyalty_update' => now(),
            'main' => 1,
            'barracks' => 0,
            'stable' => 0,
            'garage' => 0,
            'snob' => 0,
            'smith' => 0,
            'place' => 1,
            'market' => 0,
            'wood_level' => 1,
            'stone_level' => 1,
            'iron_level' => 1,
            'farm_level' => 1,
            'storage_level' => 1,
            'hide_level' => 1,
            'wall_level' => 0,
        ]);

        // Starter garrison
        VillageUnit::create([
            'village_id' => $village->id,
            'spear' => 15,
            'sword' => 10,
            'axe' => 0,
            'archer' => 0,
            'spy' => 5,
            'light' => 0,
            'marcher' => 0,
            'heavy' => 0,
            'ram' => 0,
            'catapult' => 0,
            'knight' => 1,
            'snob' => 0,
        ]);

        // Spawn a couple of barbarian villages nearby for farming
        $this->spawnNearbyBarbarians($coords['x'], $coords['y'], 3);

        return $village;
    }

    /**
     * Spawn a barbarian village at (x, y).
     */
    public function spawnBarbarianVillage(int $x, int $y, int $points = 26): Village
    {
        $village = Village::create([
            'user_id' => null,
            'name' => 'Barbarian village',
            'x' => $x,
            'y' => $y,
            'points' => $points,
            'loyalty' => 100.0,
            'wood' => 400.0,
            'clay' => 400.0,
            'iron' => 300.0,
            'last_resource_update' => now(),
            'last_loyalty_update' => now(),
            'main' => 1,
            'barracks' => 0,
            'wood_level' => 1,
            'stone_level' => 1,
            'iron_level' => 1,
            'farm_level' => 1,
            'storage_level' => 1,
            'hide_level' => 1,
            'wall_level' => 0,
            'place' => 1,
        ]);

        VillageUnit::create([
            'village_id' => $village->id,
            'spear' => 0,
            'sword' => 0,
            'axe' => 0,
        ]);

        return $village;
    }

    /**
     * Find an unoccupied grid coordinate around the world center or chosen direction.
     */
    protected function findUnoccupiedCoordinate(string $direction = 'random'): array
    {
        $center = config('game.world.default_spawn_area', ['x' => 500, 'y' => 500, 'radius' => 30]);
        $baseX = $center['x'];
        $baseY = $center['y'];

        match ($direction) {
            'nw' => [$baseX -= 15, $baseY -= 15],
            'ne' => [$baseX += 15, $baseY -= 15],
            'sw' => [$baseX -= 15, $baseY += 15],
            'se' => [$baseX += 15, $baseY += 15],
            default => null,
        };

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $radius = 5 + (int) ($attempt / 3);
            $x = $baseX + rand(-$radius, $radius);
            $y = $baseY + rand(-$radius, $radius);

            $x = max(10, min(990, $x));
            $y = max(10, min(990, $y));

            if (! Village::where('x', $x)->where('y', $y)->exists()) {
                return ['x' => $x, 'y' => $y];
            }
        }

        // Fallback
        return ['x' => rand(480, 520), 'y' => rand(480, 520)];
    }

    /**
     * Spawn barbarian villages around player coordinates.
     */
    protected function spawnNearbyBarbarians(int $centerX, int $centerY, int $count = 3): void
    {
        for ($i = 0; $i < $count; $i++) {
            $offsetX = rand(-4, 4);
            $offsetY = rand(-4, 4);
            if ($offsetX === 0 && $offsetY === 0) {
                $offsetX = 2;
            }

            $x = $centerX + $offsetX;
            $y = $centerY + $offsetY;

            if (! Village::where('x', $x)->where('y', $y)->exists()) {
                $this->spawnBarbarianVillage($x, $y, rand(26, 85));
            }
        }
    }
}
