<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * List user reports with filter tabs.
     */
    public function index(Request $request, ?string $filter = null): View
    {
        $user = Auth::user();
        $query = Report::where('user_id', $user->id)->latest();

        if ($filter && in_array($filter, ['attack', 'defense', 'support', 'trade', 'other'])) {
            $query->where('type', $filter);
        }

        $reports = $query->paginate(20);

        return view('game.reports.index', [
            'reports' => $reports,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * View detailed battle/intel report.
     */
    public function show(Request $request, int $id): View|RedirectResponse
    {
        $user = Auth::user();
        $report = Report::where('id', $id)->where('user_id', $user->id)->first();

        if (! $report) {
            return redirect()->route('game.reports.index')->with('error', 'Report not found.');
        }

        if (! $report->is_read) {
            $report->is_read = true;
            $report->save();
        }

        return view('game.reports.show', [
            'report' => $report,
            'unitConfigs' => config('game.units'),
            'buildingConfigs' => config('game.buildings'),
        ]);
    }

    /**
     * Delete report.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        Report::where('id', $id)->where('user_id', $user->id)->delete();

        return redirect()->route('game.reports.index')->with('success', 'Report deleted.');
    }

    /**
     * Mark all user reports as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $user = Auth::user();
        Report::where('user_id', $user->id)->update(['is_read' => true]);

        return back()->with('success', 'All reports marked as read.');
    }
}
