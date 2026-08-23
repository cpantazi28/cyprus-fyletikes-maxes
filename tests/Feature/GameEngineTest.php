<?php

use App\Models\BuildingQueue;
use App\Models\Movement;
use App\Models\RecruitmentQueue;
use App\Models\Report;
use App\Models\User;
use App\Models\Village;
use App\Models\VillageUnit;
use App\Services\BattleEngine;
use App\Services\BuildingService;
use App\Services\GameEngine;
use App\Services\MapService;
use App\Services\MovementService;
use App\Services\RecruitmentService;
use App\Services\ResourceService;

beforeEach(function () {
    $this->resourceService = app(ResourceService::class);
    $this->buildingService = app(BuildingService::class);
    $this->recruitmentService = app(RecruitmentService::class);
    $this->movementService = app(MovementService::class);
    $this->battleEngine = app(BattleEngine::class);
    $this->gameEngine = app(GameEngine::class);
    $this->mapService = app(MapService::class);
});

test('resource service accrues wood, clay, and iron and caps at warehouse capacity', function () {
    $village = Village::create([
        'user_id' => null,
        'name' => 'Resource Village',
        'x' => 500,
        'y' => 500,
        'wood' => 100.0,
        'clay' => 100.0,
        'iron' => 100.0,
        'wood_level' => 10,  // 122/hr at speed 1
        'stone_level' => 10,
        'iron_level' => 10,
        'storage_level' => 1, // 1000 max capacity
        'last_resource_update' => now()->subHours(10),
    ]);

    $this->resourceService->accrueResources($village);
    $village->refresh();

    // 10 hours * 122 = 1220 + 100 = 1320, but capped at 1000 warehouse capacity
    expect($village->wood)->toBe(1000.0)
        ->and($village->clay)->toBe(1000.0)
        ->and($village->iron)->toBe(1000.0);
});

test('building service validates requirements, queues upgrades, and deducts resources', function () {
    $user = User::factory()->create();
    $village = Village::create([
        'user_id' => $user->id,
        'name' => 'HQ Village',
        'x' => 501,
        'y' => 501,
        'wood' => 1000.0,
        'clay' => 1000.0,
        'iron' => 1000.0,
        'main' => 3,
        'barracks' => 0,
        'farm_level' => 10,
        'storage_level' => 10,
        'last_resource_update' => now(),
    ]);

    // Barracks requires main level 3
    $check = $this->buildingService->canUpgrade($village, 'barracks');
    expect($check['allowed'])->toBeTrue();

    $queue = $this->buildingService->queueUpgrade($village, 'barracks');
    expect($queue)->toBeInstanceOf(BuildingQueue::class)
        ->and($queue->level)->toBe(1)
        ->and($queue->status)->toBe('in_progress');

    $village->refresh();
    expect($village->wood)->toBeLessThan(1000.0);

    // Fast-forward building queue completion
    $queue->update(['finishes_at' => now()->subSecond()]);
    $this->buildingService->completeUpgrade($queue);

    $village->refresh();
    expect($village->barracks)->toBe(1)
        ->and($village->points)->toBeGreaterThan(26);
});

test('recruitment service queues and processes unit recruitment batches', function () {
    $user = User::factory()->create();
    $village = Village::create([
        'user_id' => $user->id,
        'name' => 'Barracks Village',
        'x' => 502,
        'y' => 502,
        'wood' => 5000.0,
        'clay' => 5000.0,
        'iron' => 5000.0,
        'barracks' => 5,
        'farm_level' => 10,
        'storage_level' => 10,
        'last_resource_update' => now(),
    ]);

    VillageUnit::create(['village_id' => $village->id]);

    $queue = $this->recruitmentService->queueRecruitment($village, 'spear', 10);
    expect($queue)->toBeInstanceOf(RecruitmentQueue::class)
        ->and($queue->count)->toBe(10);

    // Fast-forward time to finish all 10 units
    $queue->update([
        'starts_at' => now()->subMinutes(30),
        'finishes_at' => now()->subMinutes(5),
    ]);

    $this->recruitmentService->processVillageRecruitment($village);

    $unitRecord = VillageUnit::where('village_id', $village->id)->first();
    expect($unitRecord->spear)->toBe(10);
});

test('movement service calculates travel duration and dispatches attack', function () {
    $user = User::factory()->create();
    $source = Village::create([
        'user_id' => $user->id,
        'name' => 'Attacker Village',
        'x' => 500,
        'y' => 500,
        'place' => 1,
    ]);
    VillageUnit::create([
        'village_id' => $source->id,
        'spear' => 50,
        'light' => 20,
    ]);

    $target = Village::create([
        'user_id' => null,
        'name' => 'Target Barbarian',
        'x' => 503,
        'y' => 504, // Euclidean distance = 5 fields
    ]);
    VillageUnit::create(['village_id' => $target->id]);

    // Distance = 5. Slowest unit is spear (18 min/field) -> total 90 minutes = 5400 seconds
    $duration = $this->movementService->calculateTravelDuration($source, $target, ['spear' => 10, 'light' => 10]);
    expect($duration)->toBe(5400);

    $movement = $this->movementService->dispatchMovement($source, $target, 'attack', ['spear' => 10, 'light' => 5]);
    expect($movement)->toBeInstanceOf(Movement::class)
        ->and($movement->type)->toBe('attack')
        ->and($movement->status)->toBe('en_route');

    $source->refresh();
    expect($source->units->spear)->toBe(40)
        ->and($source->units->light)->toBe(15);
});

test('battle engine resolves combat, destroys wall with rams, plunders resources, and sends reports', function () {
    $attackerUser = User::factory()->create(['name' => 'Attacker']);
    $defenderUser = User::factory()->create(['name' => 'Defender']);

    $attackerVillage = Village::create([
        'user_id' => $attackerUser->id,
        'name' => 'Attacking Fortress',
        'x' => 500,
        'y' => 500,
        'place' => 1,
    ]);
    VillageUnit::create(['village_id' => $attackerVillage->id]);

    $defenderVillage = Village::create([
        'user_id' => $defenderUser->id,
        'name' => 'Defending Village',
        'x' => 502,
        'y' => 500,
        'wood' => 3000.0,
        'clay' => 3000.0,
        'iron' => 3000.0,
        'hide_level' => 1, // 150 protected
        'wall_level' => 5,
        'last_resource_update' => now(),
    ]);
    VillageUnit::create([
        'village_id' => $defenderVillage->id,
        'spear' => 20,
        'sword' => 10,
    ]);

    // Attack with overwhelming light cavalry and rams
    $movement = Movement::create([
        'user_id' => $attackerUser->id,
        'target_user_id' => $defenderUser->id,
        'source_village_id' => $attackerVillage->id,
        'target_village_id' => $defenderVillage->id,
        'type' => 'attack',
        'units' => ['light' => 100, 'ram' => 30],
        'departs_at' => now()->subMinutes(20),
        'arrives_at' => now(),
        'status' => 'en_route',
    ]);

    $this->battleEngine->resolveBattle($movement);

    $defenderVillage->refresh();
    // Defender troops wiped out and wall damaged
    expect($defenderVillage->units->spear)->toBe(0)
        ->and($defenderVillage->units->sword)->toBe(0)
        ->and($defenderVillage->wall_level)->toBeLessThan(5);

    // Reports generated for both players
    expect(Report::where('user_id', $attackerUser->id)->count())->toBe(1)
        ->and(Report::where('user_id', $defenderUser->id)->count())->toBe(1);

    // Returning movement created with plunder
    $returnMovement = Movement::where('target_village_id', $attackerVillage->id)
        ->where('type', 'return')
        ->first();

    expect($returnMovement)->not->toBeNull()
        ->and($returnMovement->haul)->not->toBeNull();
});

test('battle engine conquers village when loyalty drops to zero or below', function () {
    $attackerUser = User::factory()->create(['name' => 'Conqueror']);
    $defenderUser = User::factory()->create(['name' => 'Victim']);

    $attackerVillage = Village::create([
        'user_id' => $attackerUser->id,
        'name' => 'Conqueror HQ',
        'x' => 500,
        'y' => 500,
        'place' => 1,
    ]);
    VillageUnit::create(['village_id' => $attackerVillage->id]);

    $defenderVillage = Village::create([
        'user_id' => $defenderUser->id,
        'name' => 'Weak Village',
        'x' => 501,
        'y' => 500,
        'loyalty' => 15.0, // Low loyalty so 1 noble hit will conquer it
        'wall_level' => 0,
        'last_resource_update' => now(),
        'last_loyalty_update' => now(),
    ]);
    VillageUnit::create(['village_id' => $defenderVillage->id]);

    $movement = Movement::create([
        'user_id' => $attackerUser->id,
        'target_user_id' => $defenderUser->id,
        'source_village_id' => $attackerVillage->id,
        'target_village_id' => $defenderVillage->id,
        'type' => 'attack',
        'units' => ['axe' => 50, 'snob' => 1],
        'departs_at' => now()->subMinutes(10),
        'arrives_at' => now(),
        'status' => 'en_route',
    ]);

    $this->battleEngine->resolveBattle($movement);

    $defenderVillage->refresh();
    expect($defenderVillage->user_id)->toBe($attackerUser->id)
        ->and($defenderVillage->loyalty)->toBe(25.0);
});

test('map service returns 2D sector grid with correct coordinates and relationships', function () {
    $user = User::factory()->create();
    $village = Village::create([
        'user_id' => $user->id,
        'name' => 'Center Village',
        'x' => 500,
        'y' => 500,
        'points' => 150,
    ]);

    $sector = $this->mapService->getSector(500, 500, 3, $village);

    expect($sector['center_x'])->toBe(500)
        ->and($sector['min_x'])->toBe(497)
        ->and($sector['max_x'])->toBe(503)
        ->and(count($sector['grid']))->toBe(7);
});

test('game web routes load successfully with middleware', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('game.overview'));
    $response->assertStatus(200);
    $response->assertSee('Overview');

    $responseMap = $this->actingAs($user)->get(route('game.map'));
    $responseMap->assertStatus(200);

    $responsePlace = $this->actingAs($user)->get(route('game.place'));
    $responsePlace->assertStatus(200);
});
