<?php

namespace Database\Seeders;

use App\Models\Tribe;
use App\Models\TribeMember;
use App\Models\User;
use App\Models\Village;
use App\Models\VillageUnit;
use App\Services\VillageSpawnerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorldSeeder extends Seeder
{
    public function run(): void
    {
        $spawner = new VillageSpawnerService;

        // 1. Create Demo / Test Player
        $demoUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Commander',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create player village if not existing
        if ($demoUser->villages()->count() === 0) {
            $demoVillage = Village::create([
                'user_id' => $demoUser->id,
                'name' => "Commander's Castle",
                'x' => 500,
                'y' => 500,
                'points' => 312,
                'loyalty' => 100.0,
                'wood' => 2500.0,
                'clay' => 2500.0,
                'iron' => 2000.0,
                'last_resource_update' => now(),
                'last_loyalty_update' => now(),
                'main' => 5,
                'barracks' => 5,
                'stable' => 3,
                'garage' => 1,
                'snob' => 0,
                'smith' => 5,
                'place' => 1,
                'market' => 3,
                'wood_level' => 8,
                'stone_level' => 8,
                'iron_level' => 7,
                'farm_level' => 8,
                'storage_level' => 8,
                'hide_level' => 2,
                'wall_level' => 4,
            ]);

            VillageUnit::create([
                'village_id' => $demoVillage->id,
                'spear' => 60,
                'sword' => 40,
                'axe' => 80,
                'archer' => 25,
                'spy' => 15,
                'light' => 30,
                'marcher' => 10,
                'heavy' => 10,
                'ram' => 10,
                'catapult' => 5,
                'knight' => 1,
                'snob' => 0,
            ]);
        }

        // 2. Create Rival Players
        $rival1 = User::firstOrCreate(
            ['email' => 'warlord@example.com'],
            [
                'name' => 'Warlord Ragnar',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($rival1->villages()->count() === 0) {
            $v1 = Village::create([
                'user_id' => $rival1->id,
                'name' => 'Valhalla Fortress',
                'x' => 504,
                'y' => 497,
                'points' => 450,
                'loyalty' => 100.0,
                'wood' => 1800.0,
                'clay' => 1800.0,
                'iron' => 1500.0,
                'last_resource_update' => now(),
                'last_loyalty_update' => now(),
                'main' => 8,
                'barracks' => 6,
                'stable' => 4,
                'garage' => 2,
                'smith' => 6,
                'place' => 1,
                'market' => 4,
                'wood_level' => 10,
                'stone_level' => 10,
                'iron_level' => 9,
                'farm_level' => 10,
                'storage_level' => 10,
                'hide_level' => 3,
                'wall_level' => 6,
            ]);

            VillageUnit::create([
                'village_id' => $v1->id,
                'spear' => 40,
                'sword' => 30,
                'axe' => 120,
                'spy' => 10,
                'light' => 45,
                'ram' => 15,
            ]);
        }

        $rival2 = User::firstOrCreate(
            ['email' => 'duke@example.com'],
            [
                'name' => 'Duke Gerald',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        if ($rival2->villages()->count() === 0) {
            $v2 = Village::create([
                'user_id' => $rival2->id,
                'name' => 'Lionheart Citadel',
                'x' => 496,
                'y' => 503,
                'points' => 520,
                'loyalty' => 100.0,
                'wood' => 3000.0,
                'clay' => 3000.0,
                'iron' => 2800.0,
                'last_resource_update' => now(),
                'last_loyalty_update' => now(),
                'main' => 10,
                'barracks' => 8,
                'stable' => 5,
                'garage' => 2,
                'smith' => 8,
                'place' => 1,
                'market' => 5,
                'wood_level' => 12,
                'stone_level' => 12,
                'iron_level' => 11,
                'farm_level' => 12,
                'storage_level' => 12,
                'hide_level' => 4,
                'wall_level' => 8,
            ]);

            VillageUnit::create([
                'village_id' => $v2->id,
                'spear' => 120,
                'sword' => 100,
                'archer' => 50,
                'heavy' => 25,
            ]);
        }

        // 3. Create Sample Tribes
        $tribe1 = Tribe::firstOrCreate(
            ['tag' => 'WAR'],
            [
                'name' => 'Iron Vanguard',
                'description' => 'We fight for glory, honor, and domination across all continents.',
                'founder_id' => $demoUser->id,
                'points' => 0,
                'rank' => 1,
                'member_count' => 1,
            ]
        );

        TribeMember::firstOrCreate(
            ['user_id' => $demoUser->id],
            ['tribe_id' => $tribe1->id, 'role' => 'founder', 'joined_at' => now()]
        );

        $tribe2 = Tribe::firstOrCreate(
            ['tag' => 'KINGS'],
            [
                'name' => 'Royal Legion',
                'description' => 'The crown demands obedience.',
                'founder_id' => $rival1->id,
                'points' => 0,
                'rank' => 2,
                'member_count' => 2,
            ]
        );

        TribeMember::firstOrCreate(
            ['user_id' => $rival1->id],
            ['tribe_id' => $tribe2->id, 'role' => 'founder', 'joined_at' => now()]
        );

        TribeMember::firstOrCreate(
            ['user_id' => $rival2->id],
            ['tribe_id' => $tribe2->id, 'role' => 'baron', 'joined_at' => now()]
        );

        $tribe1->recalculatePoints();
        $tribe2->recalculatePoints();

        // 4. Populate Barbarian Villages around K55
        $barbarianCoords = [
            ['x' => 498, 'y' => 499, 'pts' => 45],
            ['x' => 502, 'y' => 501, 'pts' => 68],
            ['x' => 501, 'y' => 497, 'pts' => 110],
            ['x' => 497, 'y' => 505, 'pts' => 84],
            ['x' => 505, 'y' => 503, 'pts' => 150],
            ['x' => 493, 'y' => 496, 'pts' => 38],
            ['x' => 506, 'y' => 495, 'pts' => 72],
            ['x' => 495, 'y' => 508, 'pts' => 95],
            ['x' => 508, 'y' => 507, 'pts' => 135],
        ];

        foreach ($barbarianCoords as $bc) {
            if (! Village::where('x', $bc['x'])->where('y', $bc['y'])->exists()) {
                $spawner->spawnBarbarianVillage($bc['x'], $bc['y'], $bc['pts']);
            }
        }
    }
}
