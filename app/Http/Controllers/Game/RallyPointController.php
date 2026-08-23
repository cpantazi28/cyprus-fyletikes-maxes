<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Village;
use App\Models\VillageSupport;
use App\Models\VillageUnit;
use App\Services\GameEngine;
use App\Services\MovementService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RallyPointController extends Controller
{
    public function __construct(
        protected MovementService $movementService,
        protected GameEngine $gameEngine
    ) {}

    /**
     * Display Rally Point commands, stationed troops, and troop movement form.
     */
    public function index(Request $request): View
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');
        $this->gameEngine->tickVillage($village);

        $villageUnits = $village->units ?: VillageUnit::create(['village_id' => $village->id]);
        $outgoingMovements = $village->outgoingMovements()->with('targetVillage.user')->get();
        $incomingMovements = $village->incomingMovements()->with('sourceVillage.user')->get();
        $stationedSupports = $village->stationedSupports()->with('homeVillage.user')->get();
        $mySupportsOut = $village->supportsOut()->with('stationedVillage.user')->get();

        return view('game.buildings.place', [
            'village' => $village,
            'units' => config('game.units'),
            'villageUnits' => $villageUnits,
            'outgoingMovements' => $outgoingMovements,
            'incomingMovements' => $incomingMovements,
            'stationedSupports' => $stationedSupports,
            'mySupportsOut' => $mySupportsOut,
            'targetX' => $request->query('x'),
            'targetY' => $request->query('y'),
        ]);
    }

    /**
     * Confirmation screen before dispatching attack or support.
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $x = (int) $request->input('x');
        $y = (int) $request->input('y');
        $type = $request->input('type', 'attack'); // 'attack' or 'support'
        $units = $request->input('units', []);

        $targetVillage = Village::where('x', $x)->where('y', $y)->first();
        if (! $targetVillage) {
            return back()->with('error', "No village found at coordinates {$x}|{$y}.");
        }

        if ($targetVillage->id === $village->id) {
            return back()->with('error', 'Cannot send troops to the same village.');
        }

        $totalUnits = 0;
        $sanitizedUnits = [];
        $unitConfigs = config('game.units');
        $sourceUnits = $village->units ?: VillageUnit::create(['village_id' => $village->id]);

        foreach ($unitConfigs as $uKey => $cfg) {
            $count = (int) ($units[$uKey] ?? 0);
            if ($count > 0) {
                if ($count > ($sourceUnits->{$uKey} ?? 0)) {
                    return back()->with('error', "Not enough {$cfg['name']} in village.");
                }
                $sanitizedUnits[$uKey] = $count;
                $totalUnits += $count;
            } else {
                $sanitizedUnits[$uKey] = 0;
            }
        }

        if ($totalUnits <= 0) {
            return back()->with('error', 'Please enter at least one unit to send.');
        }

        $distance = $village->distanceTo($targetVillage);
        $durationSeconds = $this->movementService->calculateTravelDuration($village, $targetVillage, $sanitizedUnits);
        $arrivesAt = now()->addSeconds($durationSeconds);

        return view('game.buildings.place_confirm', [
            'village' => $village,
            'targetVillage' => $targetVillage,
            'type' => $type,
            'units' => $sanitizedUnits,
            'unitConfigs' => $unitConfigs,
            'distance' => $distance,
            'durationSeconds' => $durationSeconds,
            'arrivesAt' => $arrivesAt,
            'buildings' => config('game.buildings'),
        ]);
    }

    /**
     * Dispatch troop movement.
     */
    public function send(Request $request): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $targetVillageId = (int) $request->input('target_village_id');
        $targetVillage = Village::find($targetVillageId);

        if (! $targetVillage) {
            return redirect()->route('game.place')->with('error', 'Target village not found.');
        }

        $type = $request->input('type', 'attack');
        $units = $request->input('units', []);
        $catapultTarget = $request->input('catapult_target');

        try {
            $this->movementService->dispatchMovement($village, $targetVillage, $type, $units, $catapultTarget);
            $typeName = ucfirst($type);

            return redirect()->route('game.place')->with('success', "{$typeName} dispatched towards {$targetVillage->name} ({$targetVillage->coordinates}).");
        } catch (Exception $e) {
            return redirect()->route('game.place')->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel movement command within grace period.
     */
    public function cancel(Request $request, int $movementId): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $movement = Movement::where('id', $movementId)
            ->where('source_village_id', $village->id)
            ->first();

        if (! $movement) {
            return back()->with('error', 'Movement command not found.');
        }

        try {
            $this->movementService->cancelMovement($movement);

            return back()->with('success', 'Troop movement cancelled. The troops are returning home.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send foreign stationed support troops back home.
     */
    public function sendBackSupport(Request $request, int $supportId): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $support = VillageSupport::where('id', $supportId)
            ->where(function ($q) use ($village) {
                $q->where('stationed_village_id', $village->id)
                    ->orWhere('home_village_id', $village->id);
            })
            ->first();

        if (! $support) {
            return back()->with('error', 'Stationed troops record not found.');
        }

        $homeVillage = $support->homeVillage;
        $stationedVillage = $support->stationedVillage;
        $units = $support->units ?? [];

        $duration = $this->movementService->calculateTravelDuration($stationedVillage, $homeVillage, $units);

        // Create return movement
        Movement::create([
            'user_id' => $homeVillage->user_id,
            'target_user_id' => null,
            'source_village_id' => $stationedVillage->id,
            'target_village_id' => $homeVillage->id,
            'type' => 'return',
            'units' => $units,
            'haul' => null,
            'departs_at' => now(),
            'arrives_at' => now()->addSeconds($duration),
            'status' => 'en_route',
        ]);

        $support->delete();

        return back()->with('success', 'Supporting troops sent back home.');
    }

    /**
     * Interactive Battle Simulator.
     */
    public function simulator(Request $request): View
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');
        $unitConfigs = config('game.units');

        $attackerInput = $request->input('att', []);
        $defenderInput = $request->input('def', []);
        $wallLevel = (int) $request->input('wall', 0);
        $luck = (float) $request->input('luck', 0.0);
        $morale = (float) $request->input('morale', 100.0) / 100.0;

        $results = null;

        if ($request->isMethod('post') || $request->has('simulate')) {
            // Run quick simulation
            $attInf = 0;
            $attCav = 0;
            $attArch = 0;
            foreach ($attackerInput as $k => $cnt) {
                $cnt = (int) $cnt;
                if ($cnt <= 0 || ! isset($unitConfigs[$k])) {
                    continue;
                }
                $t = $unitConfigs[$k]['type'];
                $a = $unitConfigs[$k]['attack'] * $cnt;
                if ($t === 'infantry') {
                    $attInf += $a;
                } elseif ($t === 'cavalry') {
                    $attCav += $a;
                } elseif ($t === 'archer') {
                    $attArch += $a;
                }
            }
            $totalAtt = $attInf + $attCav + $attArch;

            $defInf = 0;
            $defCav = 0;
            $defArch = 0;
            foreach ($defenderInput as $k => $cnt) {
                $cnt = (int) $cnt;
                if ($cnt <= 0 || ! isset($unitConfigs[$k])) {
                    continue;
                }
                $defInf += $unitConfigs[$k]['def_infantry'] * $cnt;
                $defCav += $unitConfigs[$k]['def_cavalry'] * $cnt;
                $defArch += $unitConfigs[$k]['def_archer'] * $cnt;
            }

            $weightedDef = 0;
            if ($totalAtt > 0) {
                $weightedDef = (($attInf / $totalAtt) * $defInf) + (($attCav / $totalAtt) * $defCav) + (($attArch / $totalAtt) * $defArch);
            } else {
                $weightedDef = $defInf + $defCav + $defArch;
            }

            $wallBonus = config("game.buildings.wall.defense_bonus.{$wallLevel}", 0);
            $wallBase = config("game.buildings.wall.base_defense.{$wallLevel}", 20);
            $totalDef = ($weightedDef * (1.0 + ($wallBonus / 100))) + $wallBase;

            $effAtt = $totalAtt * (1.0 + ($luck / 100)) * $morale;

            $attLossRatio = 1.0;
            $defLossRatio = 1.0;
            if ($effAtt > $totalDef && $totalAtt > 0) {
                $attWins = true;
                $defLossRatio = 1.0;
                $attLossRatio = min(1.0, pow($totalDef / max(1.0, $effAtt), 1.5));
            } else {
                $attWins = false;
                $attLossRatio = 1.0;
                $defLossRatio = $totalDef > 0 ? min(1.0, pow($effAtt / max(1.0, $totalDef), 1.5)) : 1.0;
            }

            $attLosses = [];
            $defLosses = [];
            foreach ($unitConfigs as $k => $c) {
                $aCount = (int) ($attackerInput[$k] ?? 0);
                $dCount = (int) ($defenderInput[$k] ?? 0);
                $attLosses[$k] = min($aCount, (int) round($aCount * $attLossRatio));
                $defLosses[$k] = min($dCount, (int) round($dCount * $defLossRatio));
            }

            $results = [
                'attacker_wins' => $attWins,
                'att_losses' => $attLosses,
                'def_losses' => $defLosses,
            ];
        }

        return view('game.buildings.simulator', [
            'village' => $village,
            'unitConfigs' => $unitConfigs,
            'attackerInput' => $attackerInput,
            'defenderInput' => $defenderInput,
            'wallLevel' => $wallLevel,
            'luck' => $luck,
            'morale' => $morale * 100,
            'results' => $results,
        ]);
    }
}
