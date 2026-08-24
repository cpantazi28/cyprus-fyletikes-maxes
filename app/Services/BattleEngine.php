<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Report;
use App\Models\Village;
use App\Models\VillageSupport;
use App\Models\VillageUnit;

class BattleEngine {
    public function __construct(
        protected ResourceService $resourceService
    ) {
    }

    /**
     * Resolve an attack movement and its combat outcomes.
     */
    public function resolveBattle(Movement $movement): void {
        $attackerVillage = $movement->sourceVillage;
        $defenderVillage = $movement->targetVillage;

        // Ensure fresh resources & loyalty
        $this->resourceService->accrueResources($attackerVillage);
        $this->resourceService->accrueResources($defenderVillage);

        $attackerUnits = $movement->units ?? [];
        $defenderHomeUnits = $defenderVillage->units ?: VillageUnit::create(['village_id' => $defenderVillage->id]);
        $defenderSupports = VillageSupport::where('stationed_village_id', $defenderVillage->id)->get();

        // 1. Compile total defending army
        $totalDefenderUnits = $defenderHomeUnits->toArrayOfUnits();
        foreach ($defenderSupports as $sup) {
            foreach ($sup->units ?? [] as $uKey => $cnt) {
                $totalDefenderUnits[$uKey] = ($totalDefenderUnits[$uKey] ?? 0) + $cnt;
            }
        }

        // 2. Calculate Offense & Defense powers
        $unitConfigs = config('game.units', []);

        $attInfantry = 0;
        $attCavalry = 0;
        $attArcher = 0;

        foreach ($attackerUnits as $uKey => $cnt) {
            if ($cnt <= 0 || ! isset($unitConfigs[$uKey])) {
                continue;
            }
            $type = $unitConfigs[$uKey]['type'];
            $atk = $unitConfigs[$uKey]['attack'] * $cnt;
            if ($type === 'infantry') {
                $attInfantry += $atk;
            } elseif ($type === 'cavalry') {
                $attCavalry += $atk;
            } elseif ($type === 'archer') {
                $attArcher += $atk;
            }
        }

        $totalOffense = $attInfantry + $attCavalry + $attArcher;

        // Calculate defender raw defense per category
        $defInfantry = 0;
        $defCavalry = 0;
        $defArcher = 0;

        foreach ($totalDefenderUnits as $uKey => $cnt) {
            if ($cnt <= 0 || ! isset($unitConfigs[$uKey])) {
                continue;
            }
            $defInfantry += $unitConfigs[$uKey]['def_infantry'] * $cnt;
            $defCavalry += $unitConfigs[$uKey]['def_cavalry'] * $cnt;
            $defArcher += $unitConfigs[$uKey]['def_archer'] * $cnt;
        }

        // Weighted defense based on attacker unit ratio
        $weightedDefense = 0;
        if ($totalOffense > 0) {
            $weightedDefense = (
                ($attInfantry / $totalOffense) * $defInfantry +
                ($attCavalry / $totalOffense) * $defCavalry +
                ($attArcher / $totalOffense) * $defArcher
            );
        } else {
            $weightedDefense = $defInfantry + $defCavalry + $defArcher;
        }

        // 3. Wall bonus and Basic Defense
        $wallLevel = $defenderVillage->wall_level;
        $wallBonuses = config('game.buildings.wall.defense_bonus', []);
        $wallBases = config('game.buildings.wall.base_defense', []);

        $wallBonusPercent = $wallBonuses[$wallLevel] ?? 0;
        $wallBasePoints = $wallBases[$wallLevel] ?? 20;

        // Ram wall reduction during battle
        $ramsCount = $attackerUnits['ram'] ?? 0;
        $wallDropBefore = 0;
        if ($ramsCount > 0 && $wallLevel > 0) {
            $wallDropBefore = min($wallLevel, (int) floor($ramsCount / 8));
        }
        $effectiveWallLevel = max(0, $wallLevel - $wallDropBefore);
        $effectiveWallBonus = $wallBonuses[$effectiveWallLevel] ?? 0;

        $totalDefense = ($weightedDefense * (1.0 + ($effectiveWallBonus / 100))) + $wallBasePoints;

        // 4. Morale & Luck
        $luck = rand(-25, 25) / 100.0;
        $morale = 1.0;
        if (config('game.world.morale_enabled') && $attackerVillage->user_id && $defenderVillage->user_id) {
            $attPts = max(100, $attackerVillage->user->total_points ?? 100);
            $defPts = max(100, $defenderVillage->user->total_points ?? 100);
            if ($attPts > $defPts) {
                $morale = min(1.0, max(0.3, ($defPts / $attPts) * 3 + 0.25));
            }
        }

        $effectiveOffense = $totalOffense * (1.0 + $luck) * $morale;

        // 5. Combat Resolution: determine winner and casualty rates
        $attackerWins = false;
        $attackerLossRatio = 0.0;
        $defenderLossRatio = 0.0;

        if ($totalOffense <= 0) {
            // No offensive units (e.g. only scouts or empty)
            $attackerLossRatio = 1.0;
            $defenderLossRatio = 0.0;
        } elseif ($effectiveOffense > $totalDefense) {
            $attackerWins = true;
            $defenderLossRatio = 1.0;
            $attackerLossRatio = min(1.0, pow($totalDefense / max(1.0, $effectiveOffense), 1.5));
        } else {
            $attackerWins = false;
            $attackerLossRatio = 1.0;
            $defenderLossRatio = min(1.0, pow($effectiveOffense / max(1.0, $totalDefense), 1.5));
        }

        // Scout fight calculation
        $attackerScouts = $attackerUnits['spy'] ?? 0;
        $defenderScouts = $totalDefenderUnits['spy'] ?? 0;
        $scoutLossAttacker = 0;
        $scoutLossDefender = 0;
        $scoutSuccess = false;
        $scoutedBuildings = null;

        if ($attackerScouts > 0) {
            if ($defenderScouts === 0) {
                $scoutSuccess = true;
            } else {
                $scoutRatio = $defenderScouts * 2 / max(1, $attackerScouts);
                if ($scoutRatio >= 1.0) {
                    $scoutLossAttacker = $attackerScouts;
                    $scoutLossDefender = (int) round($defenderScouts * 0.5);
                } else {
                    $scoutLossAttacker = (int) round($attackerScouts * $scoutRatio);
                    $scoutLossDefender = $defenderScouts;
                    $scoutSuccess = ($attackerScouts - $scoutLossAttacker) > 0;
                }
            }
        }

        // 6. Calculate Casualties
        $attackerCasualties = [];
        $attackerSurviving = [];
        foreach ($attackerUnits as $uKey => $cnt) {
            if ($uKey === 'spy' && $attackerScouts > 0) {
                $loss = min($cnt, $scoutLossAttacker);
            } else {
                $loss = min($cnt, (int) round($cnt * $attackerLossRatio));
            }
            $attackerCasualties[$uKey] = $loss;
            $attackerSurviving[$uKey] = max(0, $cnt - $loss);
        }

        $defenderCasualties = [];
        $defenderSurviving = [];
        foreach ($totalDefenderUnits as $uKey => $cnt) {
            if ($uKey === 'spy' && $attackerScouts > 0 && $totalOffense == 0) {
                $loss = min($cnt, $scoutLossDefender);
            } else {
                $loss = min($cnt, (int) round($cnt * $defenderLossRatio));
            }
            $defenderCasualties[$uKey] = $loss;
            $defenderSurviving[$uKey] = max(0, $cnt - $loss);
        }

        // Apply casualties to Defender Home units and Support units proportionally
        $totalDefCount = array_sum($totalDefenderUnits);
        if ($totalDefCount > 0) {
            // Deduct from home units
            foreach ($defenderHomeUnits->toArrayOfUnits() as $uKey => $homeCnt) {
                $homeLoss = min($homeCnt, (int) round($homeCnt * $defenderLossRatio));
                $defenderHomeUnits->{$uKey} = max(0, $homeCnt - $homeLoss);
            }
            $defenderHomeUnits->save();

            // Deduct from support units
            foreach ($defenderSupports as $sup) {
                $supUnits = $sup->units ?? [];
                $newSupUnits = [];
                $hasTroopsLeft = false;
                foreach ($supUnits as $uKey => $supCnt) {
                    $supLoss = min($supCnt, (int) round($supCnt * $defenderLossRatio));
                    $remaining = max(0, $supCnt - $supLoss);
                    $newSupUnits[$uKey] = $remaining;
                    if ($remaining > 0) {
                        $hasTroopsLeft = true;
                    }
                }
                if ($hasTroopsLeft) {
                    $sup->units = $newSupUnits;
                    $sup->save();
                } else {
                    $sup->delete();
                }
            }
        }

        // 7. Catapult & Ram damage after combat
        $wallDamage = null;
        if ($ramsCount > 0 && $attackerWins) {
            $ramsSurvived = $attackerSurviving['ram'] ?? 0;
            if ($ramsSurvived > 0 && $defenderVillage->wall_level > 0) {
                $levelsToDrop = min($defenderVillage->wall_level, max(1, (int) floor($ramsSurvived / 5)));
                $oldWall = $defenderVillage->wall_level;
                $defenderVillage->wall_level = max(0, $defenderVillage->wall_level - $levelsToDrop);
                $defenderVillage->points = $defenderVillage->calculatePoints();
                $defenderVillage->save();
                $wallDamage = ['from' => $oldWall, 'to' => $defenderVillage->wall_level];
            }
        }

        $buildingDamage = null;
        $catapultsCount = $attackerUnits['catapult'] ?? 0;
        if ($catapultsCount > 0 && $movement->catapult_target && $attackerWins) {
            $catapultsSurvived = $attackerSurviving['catapult'] ?? 0;
            $targetBldg = $movement->catapult_target;
            $currentLevel = $defenderVillage->getBuildingLevel($targetBldg);
            $minLevel = config("game.buildings.{$targetBldg}.min_level", 0);

            if ($currentLevel > $minLevel && $catapultsSurvived > 0) {
                $drop = min($currentLevel - $minLevel, max(1, (int) floor($catapultsSurvived / 6)));
                $defenderVillage->setBuildingLevel($targetBldg, $currentLevel - $drop);
                $defenderVillage->points = $defenderVillage->calculatePoints();
                $defenderVillage->save();
                $buildingDamage = [
                    'building' => $targetBldg,
                    'from' => $currentLevel,
                    'to' => $defenderVillage->getBuildingLevel($targetBldg),
                ];
            }
        }

        // 8. Nobleman / Conquest mechanics
        $loyaltyChange = null;
        $villageConquered = false;
        $noblemenSurvived = $attackerSurviving['snob'] ?? 0;

        if ($attackerWins && $noblemenSurvived > 0 && $attackerVillage->user_id) {
            $loyaltyDrop = rand(20, 35);
            $oldLoyalty = $defenderVillage->loyalty;
            $newLoyalty = $oldLoyalty - $loyaltyDrop;
            $defenderVillage->loyalty = $newLoyalty;
            $defenderVillage->last_loyalty_update = now();

            if ($newLoyalty <= 0) {
                // Village conquered!
                $villageConquered = true;
                $defenderVillage->user_id = $attackerVillage->user_id;
                $defenderVillage->loyalty = 25.0; // Conquered starting loyalty

                // Cancel all active queues in conquered village
                $defenderVillage->buildingQueues()->where('status', 'in_progress')->delete();
                $defenderVillage->recruitmentQueues()->where('status', 'in_progress')->delete();

                // Consume 1 Nobleman
                $attackerSurviving['snob'] = max(0, $attackerSurviving['snob'] - 1);
                $attackerCasualties['snob'] = ($attackerCasualties['snob'] ?? 0) + 1;

                // Conquering troops station inside the conquered village
                $newHomeUnits = $defenderVillage->units ?: VillageUnit::create(['village_id' => $defenderVillage->id]);
                foreach ($attackerSurviving as $uKey => $cnt) {
                    $newHomeUnits->{$uKey} = ($newHomeUnits->{$uKey} ?? 0) + $cnt;
                }
                $newHomeUnits->save();
                $defenderVillage->save();

                $loyaltyChange = [
                    'from' => $oldLoyalty,
                    'to' => 25,
                    'conquered' => true,
                ];
            } else {
                $defenderVillage->save();
                $loyaltyChange = [
                    'from' => $oldLoyalty,
                    'to' => max(0, $newLoyalty),
                    'conquered' => false,
                ];
            }
        }

        // 9. Loot / Haul calculation
        $haul = ['wood' => 0, 'clay' => 0, 'iron' => 0];
        $totalCarry = 0;
        foreach ($attackerSurviving as $uKey => $cnt) {
            $totalCarry += $cnt * ($unitConfigs[$uKey]['carry'] ?? 0);
        }

        if ($attackerWins && ! $villageConquered && $totalCarry > 0) {
            $hideCap = $defenderVillage->hide_capacity;
            $lootableWood = max(0, (int) $defenderVillage->wood - $hideCap);
            $lootableClay = max(0, (int) $defenderVillage->clay - $hideCap);
            $lootableIron = max(0, (int) $defenderVillage->iron - $hideCap);
            $totalLootable = $lootableWood + $lootableClay + $lootableIron;

            if ($totalLootable > 0) {
                $takeWood = min($lootableWood, (int) round($totalCarry * ($lootableWood / $totalLootable)));
                $takeClay = min($lootableClay, (int) round($totalCarry * ($lootableClay / $totalLootable)));
                $takeIron = min($lootableIron, (int) round($totalCarry * ($lootableIron / $totalLootable)));

                $totalTaken = $takeWood + $takeClay + $takeIron;
                if ($totalTaken > $totalCarry) {
                    $scale = $totalCarry / $totalTaken;
                    $takeWood = (int) floor($takeWood * $scale);
                    $takeClay = (int) floor($takeClay * $scale);
                    $takeIron = (int) floor($takeIron * $scale);
                }

                $defenderVillage->wood = max(0, (float) $defenderVillage->wood - $takeWood);
                $defenderVillage->clay = max(0, (float) $defenderVillage->clay - $takeClay);
                $defenderVillage->iron = max(0, (float) $defenderVillage->iron - $takeIron);
                $defenderVillage->save();

                $haul = ['wood' => $takeWood, 'clay' => $takeClay, 'iron' => $takeIron];
            }
        }

        // 10. Scouting intelligence details
        if ($scoutSuccess) {
            $scoutedBuildings = [
                'main' => $defenderVillage->main,
                'barracks' => $defenderVillage->barracks,
                'stable' => $defenderVillage->stable,
                'garage' => $defenderVillage->garage,
                'snob' => $defenderVillage->snob,
                'smith' => $defenderVillage->smith,
                'place' => $defenderVillage->place,
                'market' => $defenderVillage->market,
                'wood' => $defenderVillage->wood_level,
                'stone' => $defenderVillage->stone_level,
                'iron' => $defenderVillage->iron_level,
                'farm' => $defenderVillage->farm_level,
                'storage' => $defenderVillage->storage_level,
                'hide' => $defenderVillage->hide_level,
                'wall' => $defenderVillage->wall_level,
            ];
        }

        // 11. Create return movement for surviving attackers (if not conquered)
        $survivingCount = array_sum($attackerSurviving);
        if ($survivingCount > 0 && ! $villageConquered) {
            $travelDuration = $movement->departs_at->diffInSeconds($movement->arrives_at);
            Movement::create([
                'user_id' => $movement->user_id,
                'target_user_id' => null,
                'source_village_id' => $movement->target_village_id,
                'target_village_id' => $movement->source_village_id,
                'type' => 'return',
                'units' => $attackerSurviving,
                'haul' => $haul,
                'catapult_target' => null,
                'departs_at' => now(),
                'arrives_at' => now()->addSeconds($travelDuration),
                'status' => 'en_route',
            ]);
        }

        $movement->status = 'completed';
        $movement->save();

        // 12. Create Battle Reports for both Attacker and Defender
        $reportData = [
            'attacker' => [
                'user_id' => $attackerVillage->user_id,
                'user_name' => $attackerVillage->user->name ?? 'Unknown',
                'village_id' => $attackerVillage->id,
                'village_name' => $attackerVillage->name,
                'coordinates' => $attackerVillage->coordinates,
                'units' => $attackerUnits,
                'casualties' => $attackerCasualties,
                'surviving' => $attackerSurviving,
            ],
            'defender' => [
                'user_id' => $defenderVillage->user_id,
                'user_name' => $defenderVillage->user->name ?? 'Barbarian',
                'village_id' => $defenderVillage->id,
                'village_name' => $defenderVillage->name,
                'coordinates' => $defenderVillage->coordinates,
                'units' => $totalDefenderUnits,
                'casualties' => $defenderCasualties,
                'surviving' => $defenderSurviving,
            ],
            'luck' => round($luck * 100, 1),
            'morale' => round($morale * 100, 1),
            'attacker_wins' => $attackerWins,
            'haul' => $haul,
            'wall_damage' => $wallDamage,
            'building_damage' => $buildingDamage,
            'loyalty_change' => $loyaltyChange,
            'village_conquered' => $villageConquered,
            'scout_intel' => $scoutSuccess ? [
                'resources' => [
                    'wood' => (int) $defenderVillage->wood,
                    'clay' => (int) $defenderVillage->clay,
                    'iron' => (int) $defenderVillage->iron,
                ],
                'buildings' => $scoutedBuildings,
            ] : null,
        ];

        // Send Report to Attacker
        if ($attackerVillage->user_id) {
            Report::create([
                'user_id' => $attackerVillage->user_id,
                'type' => 'attack',
                'title' => ($attackerVillage->user->name ?? 'Attacker') . ' attacks ' . $defenderVillage->name . " ({$defenderVillage->coordinates})",
                'data' => $reportData,
                'is_read' => false,
            ]);
        }

        // Send Report to Defender
        if ($defenderVillage->user_id && $defenderVillage->user_id !== $attackerVillage->user_id) {
            Report::create([
                'user_id' => $defenderVillage->user_id,
                'type' => 'defense',
                'title' => ($attackerVillage->user->name ?? 'Attacker') . ' attacks ' . $defenderVillage->name . " ({$defenderVillage->coordinates})",
                'data' => $reportData,
                'is_read' => false,
            ]);
        }
    }
}
