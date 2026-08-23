<?php

namespace App\Http\Middleware;

use App\Models\Village;
use App\Services\GameEngine;
use App\Services\VillageSpawnerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlayerVillage
{
    public function __construct(
        protected VillageSpawnerService $spawnerService,
        protected GameEngine $gameEngine
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        // Check if player has any village; if not, spawn starting village
        $userVillages = $user->villages()->with('units')->get();
        if ($userVillages->isEmpty()) {
            $startingVillage = $this->spawnerService->spawnStartingVillage($user);
            $userVillages = collect([$startingVillage]);
            session(['current_village_id' => $startingVillage->id]);
        }

        // Village switching via ?village=ID query parameter
        if ($request->has('village')) {
            $switchId = (int) $request->query('village');
            if ($userVillages->contains('id', $switchId)) {
                session(['current_village_id' => $switchId]);
            }
        }

        $currentVillageId = session('current_village_id');
        $currentVillage = $userVillages->firstWhere('id', $currentVillageId) ?? $userVillages->first();

        if ($currentVillage) {
            session(['current_village_id' => $currentVillage->id]);
            // Tick active village for instant freshness
            $this->gameEngine->tickVillage($currentVillage);
            $this->gameEngine->tickMovements();
        }

        // Share globally with views and request
        $request->attributes->set('currentVillage', $currentVillage);
        $request->attributes->set('userVillages', $userVillages);

        View::share('currentVillage', $currentVillage);
        View::share('userVillages', $userVillages);
        View::share('unreadReportsCount', $user->unreadReportsCount());
        View::share('unreadMessagesCount', $user->unreadMessagesCount());

        return $next($request);
    }
}
