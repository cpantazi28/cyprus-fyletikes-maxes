@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-2">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>⚔️ {{ $buildingConfig['name'] }} (Level {{ $currentLevel }})</span>
                </h2>
                <p class="text-xs text-[#6e431f]">{{ $buildingConfig['description'] }}</p>
            </div>
            <div class="text-xs text-right">
                <span class="text-[#7c4c23] font-bold">Recruitment speed:</span>
                <span class="bg-[#f0e2b6] px-2 py-0.5 rounded font-mono font-bold">
                    {{ round(pow(0.94, max(1, $currentLevel)) * 100) }}% of base time
                </span>
            </div>
        </div>

        <!-- Active Recruitment Queue -->
        @if($activeRecruitQueues->isNotEmpty())
        <div class="mb-4">
            <h4 class="font-bold text-xs text-[#4a290e] mb-1">Active Recruitment Orders:</h4>
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Progress</th>
                        <th>Time Remaining</th>
                        <th>Ready At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeRecruitQueues as $rq)
                    <tr>
                        <td class="font-bold">{{ $rq->unit_name }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs">{{ $rq->count_finished }} / {{ $rq->count }}</span>
                                <div class="w-24 bg-gray-300 rounded-full h-2 overflow-hidden border border-[#7d5128]">
                                    <div class="bg-green-700 h-full" style="width: {{ ($rq->count_finished / max(1, $rq->count)) * 100 }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="font-mono font-bold text-[#8a2908]" data-countdown="{{ $rq->remaining_seconds }}" data-autorefresh="true">
                            {{ gmdate('H:i:s', $rq->remaining_seconds) }}
                        </td>
                        <td>{{ $rq->finishes_at->format('H:i:s d/m') }}</td>
                        <td>
                            <form method="POST" action="{{ route('game.building.cancel-recruit', $rq->id) }}">
                                @csrf
                                <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5 px-2">Cancel</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Train Units Form -->
        <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
            🪖 Train Units
        </h3>

        <form method="POST" action="{{ route('game.building.recruit', $building) }}">
            @csrf
            <table class="tw-table mb-3">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>In Village</th>
                        <th>Cost per unit</th>
                        <th>Time per unit</th>
                        <th>Recruit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($buildingUnits as $uKey => $uCfg)
                        @php
                            $meetsReqs = $recruitmentService->meetsRequirements($village, $uKey);
                            $costTime = $recruitmentService->calculateUnitCostAndTime($village, $uKey);
                            $inVillage = $village->units?->{$uKey} ?? 0;

                            // Calculate max possible to recruit based on wood, clay, iron, pop
                            $maxWood = floor($village->wood / $costTime['wood']);
                            $maxClay = floor($village->clay / $costTime['clay']);
                            $maxIron = floor($village->iron / $costTime['iron']);
                            $maxPop = $costTime['pop'] > 0 ? floor($village->free_population / $costTime['pop']) : 9999;
                            $maxAffordable = max(0, min($maxWood, $maxClay, $maxIron, $maxPop));
                        @endphp
                        <tr>
                            <td>
                                <div class="font-bold text-[#3b200b]">{{ $uCfg['name'] }}</div>
                                <div class="text-[11px] text-[#6e431f]">
                                    ⚔️ {{ $uCfg['attack'] }} | 🛡️ Inf: {{ $uCfg['def_infantry'] }} / Cav: {{ $uCfg['def_cavalry'] }} / Arc: {{ $uCfg['def_archer'] }}
                                </div>
                                @if(!$meetsReqs)
                                    <div class="text-[11px] text-red-700 font-semibold">
                                        Requirements not met
                                    </div>
                                @endif
                            </td>
                            <td class="font-mono font-bold text-[#5a3416]">{{ number_format($inVillage) }}</td>
                            <td>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-[#8a5229]">🌲 {{ $costTime['wood'] }}</span>
                                    <span class="text-[#c46835]">🧱 {{ $costTime['clay'] }}</span>
                                    <span class="text-[#54606b]">⛏️ {{ $costTime['iron'] }}</span>
                                    <span class="text-[#3b200b]">👥 {{ $costTime['pop'] }}</span>
                                </div>
                            </td>
                            <td class="font-mono text-xs text-[#522f12]">
                                ⏱️ {{ gmdate('H:i:s', $costTime['seconds_per_unit']) }}
                            </td>
                            <td>
                                @if($meetsReqs)
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="units[{{ $uKey }}]" min="0" max="{{ $maxAffordable }}" value="0" id="unit-input-{{ $uKey }}" class="w-16 bg-white border border-[#7d5128] rounded px-1 py-0.5 text-xs text-right font-mono focus:outline-none">
                                        <button type="button" onclick="document.getElementById('unit-input-{{ $uKey }}').value = {{ $maxAffordable }}" class="text-[10px] bg-[#dfcf99] hover:bg-[#cbb878] border border-[#7d5128] rounded px-1 py-0.5 font-bold cursor-pointer" title="Max affordable ({{ $maxAffordable }})">
                                            ({{ $maxAffordable }})
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-500 italic">Locked</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end">
                <button type="submit" class="tw-btn tw-btn-success text-sm py-1.5 px-4 font-bold">
                    ⚔️ Recruit Selected Troops
                </button>
            </div>
        </form>

    </div>

</div>
@endsection
