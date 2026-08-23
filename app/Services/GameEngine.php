<?php

namespace App\Services;

use App\Models\BuildingQueue;
use App\Models\MarketTransport;
use App\Models\Movement;
use App\Models\Report;
use App\Models\Village;
use App\Models\VillageSupport;
use App\Models\VillageUnit;

class GameEngine
{
    public function __construct(
        protected ResourceService $resourceService,
        protected BuildingService $buildingService,
        protected RecruitmentService $recruitmentService,
        protected BattleEngine $battleEngine
    ) {}

    /**
     * Ticks a single village (resources, building queues, recruitment queues).
     */
    public function tickVillage(Village $village): Village
    {
        // 1. Accrue resources & loyalty
        $this->resourceService->accrueResources($village);

        // 2. Complete ready building queues
        $finishedBuildingQueues = BuildingQueue::where('village_id', $village->id)
            ->where('status', 'in_progress')
            ->where('finishes_at', '<=', now())
            ->orderBy('finishes_at')
            ->get();

        foreach ($finishedBuildingQueues as $queue) {
            $this->buildingService->completeUpgrade($queue);
        }

        // 3. Process recruitment queues
        $this->recruitmentService->processVillageRecruitment($village);

        $village->refresh();

        return $village;
    }

    /**
     * Ticks all troop movements that have arrived at their destination.
     */
    public function tickMovements(): int
    {
        $arrivedMovements = Movement::where('status', 'en_route')
            ->where('arrives_at', '<=', now())
            ->orderBy('arrives_at')
            ->get();

        $processedCount = 0;

        foreach ($arrivedMovements as $movement) {
            match ($movement->type) {
                'attack' => $this->handleAttackArrival($movement),
                'support' => $this->handleSupportArrival($movement),
                'return' => $this->handleReturnArrival($movement),
                default => null,
            };
            $processedCount++;
        }

        return $processedCount;
    }

    protected function handleAttackArrival(Movement $movement): void
    {
        $this->battleEngine->resolveBattle($movement);
    }

    protected function handleSupportArrival(Movement $movement): void
    {
        $targetVillage = $movement->targetVillage;
        $sourceVillage = $movement->sourceVillage;

        // Find or create VillageSupport record
        $support = VillageSupport::firstOrCreate(
            [
                'home_village_id' => $sourceVillage->id,
                'stationed_village_id' => $targetVillage->id,
            ],
            ['units' => []]
        );

        $existingUnits = $support->units ?? [];
        $arrivingUnits = $movement->units ?? [];

        foreach ($arrivingUnits as $uKey => $cnt) {
            $existingUnits[$uKey] = ($existingUnits[$uKey] ?? 0) + $cnt;
        }

        $support->units = $existingUnits;
        $support->save();

        $movement->status = 'completed';
        $movement->save();

        // Create notification report for supporter
        if ($sourceVillage->user_id) {
            Report::create([
                'user_id' => $sourceVillage->user_id,
                'type' => 'support',
                'title' => "Support arrived in {$targetVillage->name} ({$targetVillage->coordinates})",
                'data' => [
                    'source_village' => ['name' => $sourceVillage->name, 'coordinates' => $sourceVillage->coordinates],
                    'target_village' => ['name' => $targetVillage->name, 'coordinates' => $targetVillage->coordinates],
                    'units' => $arrivingUnits,
                ],
                'is_read' => false,
            ]);
        }

        // Create notification report for recipient
        if ($targetVillage->user_id && $targetVillage->user_id !== $sourceVillage->user_id) {
            Report::create([
                'user_id' => $targetVillage->user_id,
                'type' => 'support',
                'title' => "Support arrived from {$sourceVillage->name} ({$sourceVillage->coordinates})",
                'data' => [
                    'source_village' => ['name' => $sourceVillage->name, 'coordinates' => $sourceVillage->coordinates],
                    'target_village' => ['name' => $targetVillage->name, 'coordinates' => $targetVillage->coordinates],
                    'units' => $arrivingUnits,
                ],
                'is_read' => false,
            ]);
        }
    }

    protected function handleReturnArrival(Movement $movement): void
    {
        $homeVillage = $movement->targetVillage; // In a return movement, target is the home village
        $homeUnits = $homeVillage->units ?: VillageUnit::create(['village_id' => $homeVillage->id]);

        // Add troops back to home village
        foreach ($movement->units ?? [] as $uKey => $cnt) {
            $homeUnits->{$uKey} = ($homeUnits->{$uKey} ?? 0) + $cnt;
        }
        $homeUnits->save();

        // Deposit plundered haul
        if ($movement->haul) {
            $wood = (int) ($movement->haul['wood'] ?? 0);
            $clay = (int) ($movement->haul['clay'] ?? 0);
            $iron = (int) ($movement->haul['iron'] ?? 0);
            $this->resourceService->addResources($homeVillage, $wood, $clay, $iron);
        }

        $movement->status = 'completed';
        $movement->save();
    }

    /**
     * Ticks merchant transport arrivals.
     */
    public function tickMarketTransports(): int
    {
        $arrivedTransports = MarketTransport::where('status', 'en_route')
            ->where('arrives_at', '<=', now())
            ->orderBy('arrives_at')
            ->get();

        $count = 0;
        foreach ($arrivedTransports as $transport) {
            $targetVillage = $transport->targetVillage;
            $this->resourceService->addResources(
                $targetVillage,
                $transport->wood,
                $transport->clay,
                $transport->iron
            );

            $transport->status = 'completed';
            $transport->save();
            $count++;
        }

        return $count;
    }

    /**
     * Full game tick cycle.
     */
    public function tickAll(): array
    {
        $movementsCount = $this->tickMovements();
        $transportsCount = $this->tickMarketTransports();

        // Tick active building & recruitment queues
        $villagesWithBuildingQueues = Village::whereHas('buildingQueues', function ($q) {
            $q->where('status', 'in_progress')->where('finishes_at', '<=', now());
        })->get();

        foreach ($villagesWithBuildingQueues as $v) {
            $this->tickVillage($v);
        }

        $villagesWithRecruitQueues = Village::whereHas('recruitmentQueues', function ($q) {
            $q->where('status', 'in_progress');
        })->get();

        foreach ($villagesWithRecruitQueues as $v) {
            $this->recruitmentService->processVillageRecruitment($v);
        }

        return [
            'movements' => $movementsCount,
            'transports' => $transportsCount,
        ];
    }
}
