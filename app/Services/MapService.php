<?php

namespace App\Services;

use App\Models\Village;

class MapService
{
    /**
     * Retrieve map sector around (centerX, centerY) with radius.
     */
    public function getSector(int $centerX, int $centerY, int $radius = 7, ?Village $currentVillage = null): array
    {
        $minX = max(0, $centerX - $radius);
        $maxX = min(999, $centerX + $radius);
        $minY = max(0, $centerY - $radius);
        $maxY = min(999, $centerY + $radius);

        $villages = Village::with(['user.tribe'])
            ->whereBetween('x', [$minX, $maxX])
            ->whereBetween('y', [$minY, $maxY])
            ->get()
            ->keyBy(fn ($v) => "{$v->x}_{$v->y}");

        $grid = [];
        $currentUserId = $currentVillage?->user_id;
        $currentTribeId = $currentVillage?->user?->tribe?->id;

        for ($y = $minY; $y <= $maxY; $y++) {
            $row = [];
            for ($x = $minX; $x <= $maxX; $x++) {
                $key = "{$x}_{$y}";
                $village = $villages->get($key);

                // Deterministic terrain type based on coordinates hash
                $terrainHash = abs(crc32("{$x}_{$y}")) % 100;
                $terrain = 'grass';
                if ($terrainHash < 12) {
                    $terrain = 'forest';
                } elseif ($terrainHash < 18) {
                    $terrain = 'mountain';
                } elseif ($terrainHash < 22) {
                    $terrain = 'lake';
                }

                if ($village) {
                    $relationship = 'barbarian';
                    if ($village->user_id) {
                        if ($village->user_id === $currentUserId) {
                            $relationship = 'self';
                        } elseif ($currentTribeId && $village->user?->tribe?->id === $currentTribeId) {
                            $relationship = 'tribe';
                        } else {
                            $relationship = 'other';
                        }
                    }

                    // Village icon tier based on points
                    $points = $village->points;
                    $tier = 1;
                    if ($points >= 10000) {
                        $tier = 6;
                    } elseif ($points >= 3000) {
                        $tier = 5;
                    } elseif ($points >= 1000) {
                        $tier = 4;
                    } elseif ($points >= 300) {
                        $tier = 3;
                    } elseif ($points >= 100) {
                        $tier = 2;
                    }

                    $distance = $currentVillage ? $currentVillage->distanceTo($village) : 0;

                    $row[] = [
                        'x' => $x,
                        'y' => $y,
                        'continent' => $village->continent,
                        'terrain' => $terrain,
                        'village' => [
                            'id' => $village->id,
                            'name' => $village->name,
                            'points' => $village->points,
                            'player' => $village->user?->name ?? 'Barbarian',
                            'player_id' => $village->user_id,
                            'tribe' => $village->user?->tribe?->tag ?? null,
                            'tribe_id' => $village->user?->tribe?->id ?? null,
                            'relationship' => $relationship,
                            'tier' => $tier,
                            'distance' => $distance,
                        ],
                    ];
                } else {
                    $row[] = [
                        'x' => $x,
                        'y' => $y,
                        'continent' => 'K'.(floor($y / 100) * 10 + floor($x / 100)),
                        'terrain' => $terrain,
                        'village' => null,
                    ];
                }
            }
            $grid[] = $row;
        }

        return [
            'center_x' => $centerX,
            'center_y' => $centerY,
            'min_x' => $minX,
            'max_x' => $maxX,
            'min_y' => $minY,
            'max_y' => $maxY,
            'grid' => $grid,
        ];
    }
}
