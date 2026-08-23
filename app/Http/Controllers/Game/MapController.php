<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Village;
use App\Services\MapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function __construct(
        protected MapService $mapService
    ) {}

    /**
     * Interactive map view.
     */
    public function index(Request $request): View
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $x = (int) $request->query('x', $village->x);
        $y = (int) $request->query('y', $village->y);
        $radius = (int) $request->query('radius', 7);
        $radius = max(3, min(15, $radius));

        $sector = $this->mapService->getSector($x, $y, $radius, $village);

        return view('game.map', [
            'village' => $village,
            'sector' => $sector,
            'centerX' => $x,
            'centerY' => $y,
            'radius' => $radius,
        ]);
    }

    /**
     * API for fetching map sector data asynchronously.
     */
    public function sector(Request $request): JsonResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $x = (int) $request->query('x', $village->x);
        $y = (int) $request->query('y', $village->y);
        $radius = (int) $request->query('radius', 7);

        $sector = $this->mapService->getSector($x, $y, $radius, $village);

        return response()->json($sector);
    }
}
