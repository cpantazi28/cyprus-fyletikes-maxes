<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\BuildingQueue;
use App\Models\RecruitmentQueue;
use App\Models\Village;
use App\Services\BuildingService;
use App\Services\GameEngine;
use App\Services\RecruitmentService;
use App\Services\ResourceService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function __construct(
        protected BuildingService $buildingService,
        protected RecruitmentService $recruitmentService,
        protected ResourceService $resourceService,
        protected GameEngine $gameEngine
    ) {}

    /**
     * Display a specific building page.
     */
    public function show(Request $request, string $building): View|RedirectResponse
    {
        $buildingsConfig = config('game.buildings');
        if (! isset($buildingsConfig[$building])) {
            return redirect()->route('game.overview')->with('error', 'Invalid building.');
        }

        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');
        $this->gameEngine->tickVillage($village);

        $currentLevel = $village->getBuildingLevel($building);
        if ($currentLevel === 0 && $building !== 'main') {
            return redirect()->route('game.overview')->with('error', 'Building has not been constructed yet.');
        }

        // Special building views or generic building view
        $viewName = match ($building) {
            'main' => 'game.buildings.main',
            'barracks' => 'game.buildings.barracks',
            'stable' => 'game.buildings.stable',
            'garage' => 'game.buildings.garage',
            'snob' => 'game.buildings.snob',
            'smith' => 'game.buildings.smith',
            'market' => 'game.buildings.market',
            default => 'game.buildings.generic',
        };

        $activeRecruitQueues = RecruitmentQueue::where('village_id', $village->id)
            ->where('building', $building)
            ->where('status', 'in_progress')
            ->orderBy('starts_at')
            ->get();

        $buildingUnits = collect(config('game.units'))
            ->filter(fn ($u) => ($u['building'] ?? null) === $building)
            ->all();

        return view($viewName, [
            'village' => $village,
            'building' => $building,
            'buildingConfig' => $buildingsConfig[$building],
            'currentLevel' => $currentLevel,
            'allBuildings' => $buildingsConfig,
            'activeBuildingQueues' => $village->activeBuildingQueues,
            'activeRecruitQueues' => $activeRecruitQueues,
            'buildingUnits' => $buildingUnits,
            'buildingService' => $this->buildingService,
            'recruitmentService' => $this->recruitmentService,
        ]);
    }

    /**
     * Queue a building upgrade.
     */
    public function upgrade(Request $request, string $building): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        try {
            $this->buildingService->queueUpgrade($village, $building);

            return back()->with('success', config("game.buildings.{$building}.name").' upgrade queued.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an active building upgrade.
     */
    public function cancelUpgrade(Request $request, int $queueId): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $queue = BuildingQueue::where('id', $queueId)
            ->where('village_id', $village->id)
            ->first();

        if (! $queue) {
            return back()->with('error', 'Building order not found.');
        }

        $this->buildingService->cancelUpgrade($queue);

        return back()->with('success', 'Building order cancelled and resources refunded.');
    }

    /**
     * Queue unit recruitment in Barracks, Stable, Workshop, or Academy.
     */
    public function recruit(Request $request, string $building): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');
        $units = $request->input('units', []);

        $recruitedCount = 0;
        $errors = [];

        foreach ($units as $unitType => $amount) {
            $amount = (int) $amount;
            if ($amount <= 0) {
                continue;
            }

            $unitDef = config("game.units.{$unitType}");
            if (! $unitDef || ($unitDef['building'] ?? null) !== $building) {
                continue;
            }

            try {
                $this->recruitmentService->queueRecruitment($village, $unitType, $amount);
                $recruitedCount += $amount;
            } catch (Exception $e) {
                $errors[] = "{$unitDef['name']}: ".$e->getMessage();
            }
        }

        if ($recruitedCount > 0) {
            $msg = 'Troops recruitment started.';
            if (! empty($errors)) {
                $msg .= ' Some orders failed: '.implode(', ', $errors);
            }

            return back()->with('success', $msg);
        }

        if (! empty($errors)) {
            return back()->with('error', implode(' | ', $errors));
        }

        return back()->with('error', 'Please specify an amount of troops to recruit.');
    }

    /**
     * Cancel recruitment order.
     */
    public function cancelRecruitment(Request $request, int $queueId): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $queue = RecruitmentQueue::where('id', $queueId)
            ->where('village_id', $village->id)
            ->first();

        if (! $queue) {
            return back()->with('error', 'Recruitment order not found.');
        }

        $this->recruitmentService->cancelRecruitment($queue);

        return back()->with('success', 'Recruitment order cancelled.');
    }

    /**
     * Mint a gold coin in the Academy.
     */
    public function mintCoin(Request $request): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        if ($village->snob < 1) {
            return back()->with('error', 'Academy required to mint coins.');
        }

        $coinCost = config('game.buildings.snob.coin_cost', ['wood' => 28000, 'clay' => 30000, 'iron' => 25000]);

        $this->resourceService->accrueResources($village);
        if (! $this->resourceService->canAfford($village, $coinCost['wood'], $coinCost['clay'], $coinCost['iron'])) {
            return back()->with('error', 'Not enough resources to mint a gold coin.');
        }

        $this->resourceService->deductResources($village, $coinCost['wood'], $coinCost['clay'], $coinCost['iron']);
        $village->coins_minted += 1;
        $village->save();

        return back()->with('success', 'Gold coin minted successfully!');
    }

    /**
     * Demolish building level (requires HQ 15+).
     */
    public function demolish(Request $request, string $building): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        try {
            $this->buildingService->demolishBuilding($village, $building);

            return back()->with('success', config("game.buildings.{$building}.name").' demolished by 1 level.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
