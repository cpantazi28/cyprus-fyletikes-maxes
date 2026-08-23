<?php

namespace App\Services;

use App\Models\BuildingQueue;
use App\Models\Village;
use Exception;

class BuildingService
{
    public function __construct(
        protected ResourceService $resourceService
    ) {}

    /**
     * Get the next building level considering existing active queues.
     */
    public function getTargetLevel(Village $village, string $building): int
    {
        $currentLevel = $village->getBuildingLevel($building);
        $queuedCount = BuildingQueue::where('village_id', $village->id)
            ->where('building', $building)
            ->where('status', 'in_progress')
            ->count();

        return $currentLevel + $queuedCount + 1;
    }

    /**
     * Check if building requirements are met.
     */
    public function meetsRequirements(Village $village, string $building): bool
    {
        $reqs = config("game.buildings.{$building}.requirements", []);
        foreach ($reqs as $reqBuilding => $reqLevel) {
            if ($village->getBuildingLevel($reqBuilding) < $reqLevel) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate cost and time for upgrading a building to a specific level.
     */
    public function calculateUpgradeCost(Village $village, string $building, ?int $targetLevel = null): array
    {
        $config = config("game.buildings.{$building}");
        if (! $config) {
            throw new Exception("Invalid building: {$building}");
        }

        $level = $targetLevel ?? $this->getTargetLevel($village, $building);
        $exponent = max(0, $level - 1);

        $wood = (int) round($config['base_wood'] * pow($config['wood_factor'], $exponent));
        $clay = (int) round($config['base_clay'] * pow($config['clay_factor'], $exponent));
        $iron = (int) round($config['base_iron'] * pow($config['iron_factor'], $exponent));
        $pop = (int) round($config['base_pop'] * pow($config['pop_factor'], $exponent));

        // HQ time reduction: factor = 0.952 ^ main_level
        $mainLevel = $village->main;
        $hqFactor = pow(0.952, max(1, $mainLevel));
        $worldSpeed = config('game.world.speed', 1.0);

        $baseTime = $config['base_build_time'] * pow($config['time_factor'], $exponent);
        $buildTimeSeconds = max(5, (int) round(($baseTime * $hqFactor) / $worldSpeed));

        return [
            'level' => $level,
            'wood' => $wood,
            'clay' => $clay,
            'iron' => $iron,
            'pop' => $pop,
            'build_time_seconds' => $buildTimeSeconds,
        ];
    }

    /**
     * Check if the village can queue an upgrade for the specified building.
     */
    public function canUpgrade(Village $village, string $building): array
    {
        $config = config("game.buildings.{$building}");
        if (! $config) {
            return ['allowed' => false, 'reason' => 'Invalid building.'];
        }

        $targetLevel = $this->getTargetLevel($village, $building);
        if ($targetLevel > $config['max_level']) {
            return ['allowed' => false, 'reason' => 'Building is already at maximum level.'];
        }

        if (! $this->meetsRequirements($village, $building)) {
            return ['allowed' => false, 'reason' => 'Building requirements not met.'];
        }

        $cost = $this->calculateUpgradeCost($village, $building, $targetLevel);

        if (! $this->resourceService->canAfford($village, $cost['wood'], $cost['clay'], $cost['iron'])) {
            return ['allowed' => false, 'reason' => 'Not enough resources.', 'cost' => $cost];
        }

        if ($village->free_population < $cost['pop']) {
            return ['allowed' => false, 'reason' => 'Not enough farm population.', 'cost' => $cost];
        }

        return ['allowed' => true, 'reason' => null, 'cost' => $cost];
    }

    /**
     * Queue a building upgrade.
     */
    public function queueUpgrade(Village $village, string $building): BuildingQueue
    {
        $this->resourceService->accrueResources($village);
        $check = $this->canUpgrade($village, $building);

        if (! $check['allowed']) {
            throw new Exception($check['reason']);
        }

        $cost = $check['cost'];

        // Deduct resources
        $this->resourceService->deductResources($village, $cost['wood'], $cost['clay'], $cost['iron']);

        // Determine start time: after previous queue finishes or now
        $lastQueue = BuildingQueue::where('village_id', $village->id)
            ->where('status', 'in_progress')
            ->orderByDesc('finishes_at')
            ->first();

        $startsAt = $lastQueue ? $lastQueue->finishes_at : now();
        $finishesAt = $startsAt->copy()->addSeconds($cost['build_time_seconds']);

        return BuildingQueue::create([
            'village_id' => $village->id,
            'building' => $building,
            'level' => $cost['level'],
            'build_time_seconds' => $cost['build_time_seconds'],
            'starts_at' => $startsAt,
            'finishes_at' => $finishesAt,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Cancel a queued building upgrade with resource refund.
     */
    public function cancelUpgrade(BuildingQueue $queue): void
    {
        if ($queue->status !== 'in_progress') {
            return;
        }

        $village = $queue->village;
        $this->resourceService->accrueResources($village);

        // Calculate refund cost
        $cost = $this->calculateUpgradeCost($village, $queue->building, $queue->level);
        $this->resourceService->addResources($village, $cost['wood'], $cost['clay'], $cost['iron']);

        $queueDuration = $queue->build_time_seconds;
        $queueFinishesAt = $queue->finishes_at;
        $queueId = $queue->id;
        $villageId = $queue->village_id;

        $queue->delete();

        // Reschedule subsequent queued items
        $subsequentQueues = BuildingQueue::where('village_id', $villageId)
            ->where('status', 'in_progress')
            ->where('starts_at', '>=', $queueFinishesAt)
            ->orderBy('starts_at')
            ->get();

        foreach ($subsequentQueues as $sq) {
            $sq->starts_at = $sq->starts_at->subSeconds($queueDuration);
            $sq->finishes_at = $sq->finishes_at->subSeconds($queueDuration);
            $sq->save();
        }
    }

    /**
     * Complete a building upgrade order.
     */
    public function completeUpgrade(BuildingQueue $queue): void
    {
        $village = $queue->village;
        $village->setBuildingLevel($queue->building, $queue->level);
        $village->points = $village->calculatePoints();
        $village->save();

        $queue->status = 'completed';
        $queue->save();
    }

    /**
     * Demolish a building level (requires HQ lv 15).
     */
    public function demolishBuilding(Village $village, string $building): bool
    {
        if ($village->main < 15) {
            throw new Exception('Headquarters level 15 required for demolition.');
        }

        $currentLevel = $village->getBuildingLevel($building);
        $minLevel = config("game.buildings.{$building}.min_level", 0);

        if ($currentLevel <= $minLevel) {
            throw new Exception('Building cannot be demolished below minimum level.');
        }

        $village->setBuildingLevel($building, $currentLevel - 1);
        $village->points = $village->calculatePoints();
        $village->save();

        return true;
    }
}
