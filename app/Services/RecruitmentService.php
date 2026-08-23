<?php

namespace App\Services;

use App\Models\RecruitmentQueue;
use App\Models\Village;
use App\Models\VillageUnit;
use Exception;

class RecruitmentService
{
    public function __construct(
        protected ResourceService $resourceService
    ) {}

    /**
     * Check unit requirements.
     */
    public function meetsRequirements(Village $village, string $unitType): bool
    {
        $unitDef = config("game.units.{$unitType}");
        if (! $unitDef) {
            return false;
        }

        $reqs = $unitDef['requirements'] ?? [];
        foreach ($reqs as $building => $reqLevel) {
            if ($village->getBuildingLevel($building) < $reqLevel) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate cost and duration per unit for a given village.
     */
    public function calculateUnitCostAndTime(Village $village, string $unitType): array
    {
        $unitDef = config("game.units.{$unitType}");
        if (! $unitDef) {
            throw new Exception("Invalid unit: {$unitType}");
        }

        $buildingKey = $unitDef['building'];
        $buildingLevel = $village->getBuildingLevel($buildingKey);
        $worldSpeed = config('game.world.speed', 1.0);

        // Building speed reduction: 0.94 ^ building_level
        $timeFactor = pow(0.94, max(1, $buildingLevel));
        $secondsPerUnit = max(1, (int) round(($unitDef['recruit_time'] * $timeFactor) / $worldSpeed));

        return [
            'wood' => $unitDef['wood'],
            'clay' => $unitDef['clay'],
            'iron' => $unitDef['iron'],
            'pop' => $unitDef['pop'],
            'seconds_per_unit' => $secondsPerUnit,
        ];
    }

    /**
     * Check if a village can recruit the requested amount of units.
     */
    public function canRecruit(Village $village, string $unitType, int $amount): array
    {
        if ($amount <= 0) {
            return ['allowed' => false, 'reason' => 'Amount must be at least 1.'];
        }

        if (! $this->meetsRequirements($village, $unitType)) {
            return ['allowed' => false, 'reason' => 'Unit requirements not met.'];
        }

        $costPerUnit = $this->calculateUnitCostAndTime($village, $unitType);
        $totalWood = $costPerUnit['wood'] * $amount;
        $totalClay = $costPerUnit['clay'] * $amount;
        $totalIron = $costPerUnit['iron'] * $amount;
        $totalPop = $costPerUnit['pop'] * $amount;

        if (! $this->resourceService->canAfford($village, $totalWood, $totalClay, $totalIron)) {
            return ['allowed' => false, 'reason' => 'Not enough resources.'];
        }

        if ($village->free_population < $totalPop) {
            return ['allowed' => false, 'reason' => 'Not enough farm population.'];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'cost' => [
                'wood' => $totalWood,
                'clay' => $totalClay,
                'iron' => $totalIron,
                'pop' => $totalPop,
                'seconds_per_unit' => $costPerUnit['seconds_per_unit'],
                'total_seconds' => $costPerUnit['seconds_per_unit'] * $amount,
            ],
        ];
    }

    /**
     * Queue troop recruitment.
     */
    public function queueRecruitment(Village $village, string $unitType, int $amount): RecruitmentQueue
    {
        $this->resourceService->accrueResources($village);
        $check = $this->canRecruit($village, $unitType, $amount);

        if (! $check['allowed']) {
            throw new Exception($check['reason']);
        }

        $cost = $check['cost'];
        $unitDef = config("game.units.{$unitType}");
        $building = $unitDef['building'];

        // Deduct resources
        $this->resourceService->deductResources($village, $cost['wood'], $cost['clay'], $cost['iron']);

        // Determine start time (sequential for the same building)
        $lastQueue = RecruitmentQueue::where('village_id', $village->id)
            ->where('building', $building)
            ->where('status', 'in_progress')
            ->orderByDesc('finishes_at')
            ->first();

        $startsAt = $lastQueue ? $lastQueue->finishes_at : now();
        $finishesAt = $startsAt->copy()->addSeconds($cost['total_seconds']);

        return RecruitmentQueue::create([
            'village_id' => $village->id,
            'building' => $building,
            'unit_type' => $unitType,
            'count' => $amount,
            'count_finished' => 0,
            'seconds_per_unit' => $cost['seconds_per_unit'],
            'starts_at' => $startsAt,
            'finishes_at' => $finishesAt,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Cancel an active recruitment order with partial refund for unfinished units.
     */
    public function cancelRecruitment(RecruitmentQueue $queue): void
    {
        if ($queue->status !== 'in_progress') {
            return;
        }

        $village = $queue->village;
        $this->resourceService->accrueResources($village);
        $this->processVillageRecruitment($village);

        $remainingCount = $queue->count - $queue->count_finished;
        if ($remainingCount > 0) {
            $unitDef = config("game.units.{$queue->unit_type}");
            $refundWood = (int) ($unitDef['wood'] * $remainingCount * 0.9); // 90% refund
            $refundClay = (int) ($unitDef['clay'] * $remainingCount * 0.9);
            $refundIron = (int) ($unitDef['iron'] * $remainingCount * 0.9);

            $this->resourceService->addResources($village, $refundWood, $refundClay, $refundIron);
        }

        $queue->delete();
    }

    /**
     * Process unit recruitment progression for a village.
     */
    public function processVillageRecruitment(Village $village): void
    {
        $activeQueues = RecruitmentQueue::where('village_id', $village->id)
            ->where('status', 'in_progress')
            ->orderBy('starts_at')
            ->get();

        if ($activeQueues->isEmpty()) {
            return;
        }

        $villageUnits = VillageUnit::firstOrCreate(['village_id' => $village->id]);
        $now = now();

        foreach ($activeQueues as $queue) {
            if ($queue->starts_at->isFuture()) {
                continue;
            }

            $effectiveNow = $now->isAfter($queue->finishes_at) ? $queue->finishes_at : $now;
            $elapsedSeconds = max(0, $effectiveNow->timestamp - $queue->starts_at->timestamp);

            $totalUnitsShouldBeDone = min($queue->count, (int) floor($elapsedSeconds / $queue->seconds_per_unit));
            $newUnits = max(0, $totalUnitsShouldBeDone - $queue->count_finished);

            if ($newUnits > 0) {
                $unitType = $queue->unit_type;
                $villageUnits->{$unitType} = ($villageUnits->{$unitType} ?? 0) + $newUnits;
                $queue->count_finished = $totalUnitsShouldBeDone;

                if ($queue->count_finished >= $queue->count) {
                    $queue->status = 'completed';
                }

                $queue->save();
            }
        }

        $villageUnits->save();
    }
}
