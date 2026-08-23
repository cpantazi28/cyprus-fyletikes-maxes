<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Tribe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * Player Leaderboard.
     */
    public function players(Request $request): View
    {
        $players = User::withCount('villages')
            ->with(['tribe'])
            ->leftJoin('villages', 'users.id', '=', 'villages.user_id')
            ->select('users.*', DB::raw('COALESCE(SUM(villages.points), 0) as calculated_points'))
            ->groupBy('users.id')
            ->orderByDesc('calculated_points')
            ->paginate(25);

        return view('game.ranking.players', [
            'players' => $players,
            'tab' => 'players',
        ]);
    }

    /**
     * Tribe Leaderboard.
     */
    public function tribes(Request $request): View
    {
        $tribes = Tribe::withCount('members')
            ->orderByDesc('points')
            ->paginate(25);

        return view('game.ranking.tribes', [
            'tribes' => $tribes,
            'tab' => 'tribes',
        ]);
    }
}
