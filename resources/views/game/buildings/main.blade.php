@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <!-- Building Header -->
    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-2">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>🏛️ Headquarters (Level {{ $village->main }})</span>
                </h2>
                <p class="text-xs text-[#6e431f]">{{ $buildingConfig['description'] }}</p>
            </div>
            <div class="text-xs text-right">
                <span class="text-[#7c4c23] font-bold">Construction speed:</span>
                <span class="bg-[#f0e2b6] px-2 py-0.5 rounded font-mono font-bold">
                    {{ round(pow(0.952, max(1, $village->main)) * 100) }}% of base time
                </span>
            </div>
        </div>

        <!-- Village Rename Section -->
        <form method="POST" action="{{ route('game.village.rename') }}" class="flex items-center gap-2 text-xs mb-3 bg-[#f5ecc7] p-2 rounded border border-[#c9b183]">
            @csrf
            <span class="font-bold text-[#4a290e]">Change Village Name:</span>
            <input type="text" name="name" value="{{ $village->name }}" required minlength="2" maxlength="30" class="bg-white border border-[#7d5128] rounded px-2 py-0.5 text-xs text-[#3b200b] focus:outline-none">
            <button type="submit" class="tw-btn text-xs py-0.5">Rename</button>
        </form>

        <!-- Active Building Queues -->
        @if($activeBuildingQueues->isNotEmpty())
        <div class="mb-4">
            <h4 class="font-bold text-xs text-[#4a290e] mb-1">Active Construction Orders:</h4>
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Building</th>
                        <th>Level</th>
                        <th>Remaining Time</th>
                        <th>Ready At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeBuildingQueues as $bq)
                    <tr>
                        <td class="font-bold">{{ $bq->building_name }}</td>
                        <td>Level {{ $bq->level }}</td>
                        <td class="font-mono font-bold text-[#8a2908]" data-countdown="{{ $bq->remaining_seconds }}" data-autorefresh="true">
                            {{ gmdate('H:i:s', $bq->remaining_seconds) }}
                        </td>
                        <td>{{ $bq->finishes_at->format('H:i:s d/m') }}</td>
                        <td>
                            <form method="POST" action="{{ route('game.building.cancel-upgrade', $bq->id) }}">
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

        <!-- Building Construction & Upgrade Table -->
        <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
            🏗️ Construct / Upgrade Buildings
        </h3>

        <table class="tw-table">
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Level</th>
                    <th>Cost (Wood / Clay / Iron)</th>
                    <th>Pop</th>
                    <th>Build Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allBuildings as $bKey => $bCfg)
                    @php
                        $curLvl = $village->getBuildingLevel($bKey);
                        $targetLvl = $buildingService->getTargetLevel($village, $bKey);
                        $check = $buildingService->canUpgrade($village, $bKey);
                        $cost = $check['cost'] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('game.building.show', $bKey) }}" class="font-bold text-[#4a290e] hover:underline">
                                    {{ $bCfg['name'] }}
                                </a>
                            </div>
                            @if(!empty($bCfg['requirements']))
                                <div class="text-[11px] text-[#7d5c36]">
                                    Reqs:
                                    @foreach($bCfg['requirements'] as $reqB => $reqL)
                                        <span class="{{ $village->getBuildingLevel($reqB) >= $reqL ? 'text-green-800' : 'text-red-700 font-bold' }}">
                                            {{ config("game.buildings.{$reqB}.name") }} (Lv {{ $reqL }})
                                        </span>{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="font-bold">{{ $curLvl }}</span>
                            @if($targetLvl > $curLvl + 1)
                                <span class="text-xs text-orange-800">(&rarr; {{ $targetLvl - 1 }})</span>
                            @endif
                            <span class="text-xs text-gray-500">/ {{ $bCfg['max_level'] }}</span>
                        </td>

                        @if($cost)
                            <td>
                                <div class="flex items-center gap-2 text-xs font-semibold">
                                    <span class="{{ $village->wood < $cost['wood'] ? 'text-red-700 font-bold' : 'text-[#8a5229]' }}">
                                        🌲 {{ number_format($cost['wood']) }}
                                    </span>
                                    <span class="{{ $village->clay < $cost['clay'] ? 'text-red-700 font-bold' : 'text-[#c46835]' }}">
                                        🧱 {{ number_format($cost['clay']) }}
                                    </span>
                                    <span class="{{ $village->iron < $cost['iron'] ? 'text-red-700 font-bold' : 'text-[#54606b]' }}">
                                        ⛏️ {{ number_format($cost['iron']) }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="{{ $village->free_population < $cost['pop'] ? 'text-red-700 font-bold' : 'text-[#3b200b]' }}">
                                    👥 {{ $cost['pop'] }}
                                </span>
                            </td>
                            <td class="font-mono text-xs text-[#522f12]">
                                ⏱️ {{ gmdate('H:i:s', $cost['build_time_seconds']) }}
                            </td>
                            <td>
                                @if($check['allowed'])
                                    <form method="POST" action="{{ route('game.building.upgrade', $bKey) }}">
                                        @csrf
                                        <button type="submit" class="tw-btn tw-btn-success text-xs py-1 px-3">
                                            {{ $curLvl == 0 ? 'Construct' : 'Upgrade to level ' . $cost['level'] }}
                                        </button>
                                    </form>
                                @else
                                    <button disabled class="tw-btn text-xs py-1 px-3" title="{{ $check['reason'] }}">
                                        {{ $check['reason'] }}
                                    </button>
                                @endif
                            </td>
                        @else
                            <td colspan="4" class="text-center text-gray-500 italic text-xs">
                                {{ $check['reason'] ?? 'Maximum level reached' }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Demolition (HQ 15+) -->
        @if($village->main >= 15)
        <div class="mt-4 pt-3 border-t border-[#c2a46c]">
            <h4 class="font-bold text-xs text-red-800 mb-1">Demolish Buildings:</h4>
            <p class="text-xs text-gray-600 mb-2">Demolishing reduces the building level by 1 and frees up population.</p>
            <div class="flex flex-wrap gap-2">
                @foreach($allBuildings as $bKey => $bCfg)
                    @if($village->getBuildingLevel($bKey) > ($bCfg['min_level'] ?? 0))
                        <form method="POST" action="{{ route('game.building.demolish', $bKey) }}" onsubmit="return confirm('Demolish 1 level of {{ $bCfg['name'] }}?')">
                            @csrf
                            <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5">
                                Demolish {{ $bCfg['name'] }} (Lv {{ $village->getBuildingLevel($bKey) }})
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

    </div>

</div>
@endsection
