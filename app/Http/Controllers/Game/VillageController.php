<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Village;
use App\Services\GameEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VillageController extends Controller
{
    public function __construct(
        protected GameEngine $gameEngine
    ) {}

    /**
     * Display the village overview.
     */
    public function overview(Request $request): View
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');
        $this->gameEngine->tickVillage($village);

        $village->load([
            'units',
            'activeBuildingQueues',
            'activeRecruitmentQueues',
            'outgoingMovements.targetVillage',
            'incomingMovements.sourceVillage',
            'stationedSupports.homeVillage',
        ]);

        return view('game.overview', [
            'village' => $village,
            'buildings' => config('game.buildings'),
            'units' => config('game.units'),
        ]);
    }

    /**
     * Rename active village.
     */
    public function rename(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:30'],
        ]);

        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');
        $village->name = $request->input('name');
        $village->save();

        return back()->with('success', 'Village renamed successfully.');
    }
}
