@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <!-- Active Queues & Incoming Commands Bar -->
    @php
        $buildingQueues = $village->activeBuildingQueues;
        $recruitQueues = $village->activeRecruitmentQueues;
        $outgoing = $village->outgoingMovements;
        $incoming = $village->incomingMovements;
    @endphp

    @if($incoming->isNotEmpty() || $outgoing->isNotEmpty())
    <div class="tw-panel p-3">
        <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2 flex items-center gap-2">
            <span>⚔️ Troops in Motion</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
            <!-- Incoming -->
            <div>
                <h4 class="font-bold text-[#8a2424] mb-1">Incoming Commands ({{ $incoming->count() }}):</h4>
                @if($incoming->isEmpty())
                    <p class="text-gray-600 italic">No incoming troop movements.</p>
                @else
                    <div class="flex flex-col gap-1 max-h-32 overflow-y-auto">
                        @foreach($incoming as $inc)
                            <div class="flex items-center justify-between p-1.5 rounded {{ $inc->type === 'attack' ? 'bg-red-100 border border-red-300' : 'bg-blue-50 border border-blue-200' }}">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $inc->type === 'attack' ? '⚔️' : '🛡️' }}</span>
                                    <span class="font-semibold">{{ ucfirst($inc->type) }} from {{ $inc->sourceVillage->name }} ({{ $inc->sourceVillage->coordinates }})</span>
                                </div>
                                <span class="font-mono font-bold text-red-800" data-countdown="{{ $inc->remaining_seconds }}" data-autorefresh="true">
                                    {{ gmdate('H:i:s', $inc->remaining_seconds) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Outgoing -->
            <div>
                <h4 class="font-bold text-[#355e1e] mb-1">Outgoing Commands ({{ $outgoing->count() }}):</h4>
                @if($outgoing->isEmpty())
                    <p class="text-gray-600 italic">No outgoing troop movements.</p>
                @else
                    <div class="flex flex-col gap-1 max-h-32 overflow-y-auto">
                        @foreach($outgoing as $out)
                            <div class="flex items-center justify-between p-1.5 rounded bg-green-50 border border-green-200">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $out->type === 'attack' ? '⚔️' : ($out->type === 'return' ? '🔙' : '🛡️') }}</span>
                                    <span class="font-semibold">{{ ucfirst($out->type) }} to {{ $out->targetVillage->name }} ({{ $out->targetVillage->coordinates }})</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-green-900" data-countdown="{{ $out->remaining_seconds }}" data-autorefresh="true">
                                        {{ gmdate('H:i:s', $out->remaining_seconds) }}
                                    </span>
                                    @if($out->is_cancelable)
                                        <form method="POST" action="{{ route('game.place.cancel', $out->id) }}">
                                            @csrf
                                            <button type="submit" class="text-[10px] text-red-700 underline hover:font-bold">Cancel</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Active Construction Queues -->
    @if($buildingQueues->isNotEmpty())
    <div class="tw-panel p-3">
        <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2 flex items-center justify-between">
            <span>🔨 Building Queue</span>
            <span class="text-xs text-[#7c4c23] font-normal">Headquarters (Level {{ $village->main }})</span>
        </h3>
        <table class="tw-table">
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Target Level</th>
                    <th>Time Remaining</th>
                    <th>Completion At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buildingQueues as $bq)
                <tr>
                    <td class="font-bold text-[#3b200b]">
                        <a href="{{ route('game.building.show', $bq->building) }}" class="hover:underline text-[#7c4c23]">
                            {{ $bq->building_name }}
                        </a>
                    </td>
                    <td>Level {{ $bq->level }}</td>
                    <td class="font-mono font-bold text-[#8a2908]" data-countdown="{{ $bq->remaining_seconds }}" data-autorefresh="true">
                        {{ gmdate('H:i:s', $bq->remaining_seconds) }}
                    </td>
                    <td>{{ $bq->finishes_at->format('H:i:s d/m') }}</td>
                    <td>
                        <form method="POST" action="{{ route('game.building.cancel-upgrade', $bq->id) }}" onsubmit="return confirm('Cancel this construction order? Resources will be refunded.')">
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

    <!-- Graphic Village Visual Canvas -->
    <div class="tw-panel p-2">
        <div class="tw-panel-header px-3 py-1.5 flex justify-between items-center rounded-t">
            <span class="text-sm font-bold tracking-wide">🏰 Village Overview: {{ $village->name }}</span>
            <span class="text-xs">Loyalty: <strong class="{{ $village->loyalty < 100 ? 'text-red-300 font-bold' : 'text-green-300' }}">{{ number_format($village->loyalty, 1) }}%</strong></span>
        </div>

        @php
            $villageBuildings = [
                ['name' => 'Headquarters', 'key' => 'main', 'level' => $village->main, 'icon' => '🏛️', 'route' => 'game.building.show'],
                ['name' => 'Barracks', 'key' => 'barracks', 'level' => $village->barracks, 'icon' => '⚔️', 'route' => 'game.building.show'],
                ['name' => 'Stable', 'key' => 'stable', 'level' => $village->stable, 'icon' => '🐎', 'route' => 'game.building.show'],
                ['name' => 'Workshop', 'key' => 'garage', 'level' => $village->garage, 'icon' => '🪵', 'route' => 'game.building.show'],
                ['name' => 'Academy', 'key' => 'snob', 'level' => $village->snob, 'icon' => '👑', 'route' => 'game.building.show'],
                ['name' => 'Smithy', 'key' => 'smith', 'level' => $village->smith, 'icon' => '⚒️', 'route' => 'game.building.show'],
                ['name' => 'Rally Point', 'key' => 'place', 'level' => $village->place, 'icon' => '🚩', 'route' => 'game.place'],
                ['name' => 'Market', 'key' => 'market', 'level' => $village->market, 'icon' => '⚖️', 'route' => 'game.building.show'],
                ['name' => 'Timber Camp', 'key' => 'wood', 'level' => $village->wood_level, 'icon' => '🌲', 'route' => 'game.building.show'],
                ['name' => 'Clay Pit', 'key' => 'stone', 'level' => $village->stone_level, 'icon' => '🧱', 'route' => 'game.building.show'],
                ['name' => 'Iron Mine', 'key' => 'iron', 'level' => $village->iron_level, 'icon' => '⛏️', 'route' => 'game.building.show'],
                ['name' => 'Farm', 'key' => 'farm', 'level' => $village->farm_level, 'icon' => '🌾', 'route' => 'game.building.show'],
                ['name' => 'Warehouse', 'key' => 'storage', 'level' => $village->storage_level, 'icon' => '📦', 'route' => 'game.building.show'],
                ['name' => 'Hideout', 'key' => 'hide', 'level' => $village->hide_level, 'icon' => '🕳️', 'route' => 'game.building.show'],
            ];
        @endphp

        <!-- Isometric-style village scene with a visible perimeter and building roster -->
        <div class="relative w-full rounded overflow-hidden border border-[#522f12] my-2 bg-[#2d4d1f] shadow-inner p-3 sm:p-5" style="min-height: 460px; background-image: radial-gradient(circle at 50% 50%, #477832 0%, #203b14 100%);">
            <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(#d5c28a 1.5px, transparent 1.5px); background-size: 24px 24px;"></div>
            <div class="relative min-h-[410px] rounded-lg border-4 border-[#8b6a35] bg-[#477832]/50 p-2 sm:p-4 shadow-[inset_0_0_30px_rgba(19,45,12,0.65)]">
                <div class="absolute inset-2 sm:inset-4 border-2 border-dashed border-[#d5c28a]/50 rounded pointer-events-none"></div>

                <!-- Four clickable wall sections make the wall level visible as part of the settlement -->
                <a href="{{ route('game.building.show', 'wall') }}" class="absolute top-0 left-1/2 -translate-x-1/2 w-3/4 h-5 sm:h-7 bg-[#88704b] border-2 border-[#d8ba6a] shadow-lg flex items-center justify-center z-10 hover:brightness-125" title="Wall Level {{ $village->wall_level }}">
                    <span class="text-[10px] sm:text-xs font-bold text-[#2b1704]">🛡️ North Wall · Lv {{ $village->wall_level }}</span>
                </a>
                <a href="{{ route('game.building.show', 'wall') }}" class="absolute bottom-0 left-1/2 -translate-x-1/2 w-3/4 h-5 sm:h-7 bg-[#88704b] border-2 border-[#d8ba6a] shadow-lg flex items-center justify-center z-10 hover:brightness-125" title="Wall Level {{ $village->wall_level }}">
                    <span class="text-[10px] sm:text-xs font-bold text-[#2b1704]">South Wall · Lv {{ $village->wall_level }}</span>
                </a>
                <a href="{{ route('game.building.show', 'wall') }}" class="absolute left-0 top-1/2 -translate-y-1/2 w-5 sm:w-7 h-3/4 bg-[#88704b] border-2 border-[#d8ba6a] shadow-lg flex items-center justify-center z-10 hover:brightness-125" title="Wall Level {{ $village->wall_level }}">
                    <span class="text-[10px] font-bold text-[#2b1704] [writing-mode:vertical-rl]">West · Lv {{ $village->wall_level }}</span>
                </a>
                <a href="{{ route('game.building.show', 'wall') }}" class="absolute right-0 top-1/2 -translate-y-1/2 w-5 sm:w-7 h-3/4 bg-[#88704b] border-2 border-[#d8ba6a] shadow-lg flex items-center justify-center z-10 hover:brightness-125" title="Wall Level {{ $village->wall_level }}">
                    <span class="text-[10px] font-bold text-[#2b1704] [writing-mode:vertical-rl]">East · Lv {{ $village->wall_level }}</span>
                </a>

                <div class="relative z-0 grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 pt-5 pb-5 sm:pt-8 sm:pb-8 px-5 sm:px-8">
                    @foreach($villageBuildings as $building)
                        <a href="{{ $building['route'] === 'game.place' ? route($building['route']) : route($building['route'], $building['key']) }}" class="group min-h-[88px] sm:min-h-[104px] flex flex-col items-center justify-center p-2 rounded-md border-2 {{ $building['level'] > 0 ? 'bg-[#5a3416]/90 border-[#caa552] text-[#fff2cc]' : 'bg-[#332516]/70 border-[#8b785c] text-[#b8aa8e]' }} shadow-md transition-all hover:-translate-y-1 hover:brightness-125">
                            <span class="text-2xl sm:text-3xl leading-none mb-1 {{ $building['level'] === 0 ? 'grayscale opacity-70' : '' }}">{{ $building['icon'] }}</span>
                            <span class="text-[10px] sm:text-xs font-bold text-center leading-tight">{{ $building['name'] }}</span>
                            <span class="text-[10px] sm:text-[11px] bg-[#361c07] px-1.5 py-0.5 rounded mt-1 font-bold {{ $building['level'] > 0 ? 'text-yellow-400' : 'text-[#b8aa8e]' }}">Lv {{ $building['level'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <!-- Stationed Troops & Production Rates Table -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

        <!-- Troops in Village -->
        <div class="tw-panel p-3">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2 flex items-center justify-between">
                <span>🛡️ Stationed Troops</span>
                <a href="{{ route('game.place') }}" class="text-xs text-[#7c4c23] hover:underline">Rally Point &rarr;</a>
            </h3>

            @php
                $unitsData = $village->units ? $village->units->toArrayOfUnits() : [];
                $hasTroops = false;
            @endphp

            <table class="tw-table">
                <tbody>
                    @foreach(config('game.units') as $uKey => $uCfg)
                        @if(($unitsData[$uKey] ?? 0) > 0)
                            @php $hasTroops = true; @endphp
                            <tr>
                                <td class="font-bold text-[#3b200b]">{{ $uCfg['name'] }}</td>
                                <td class="text-right font-mono font-bold text-[#5a3416]">{{ number_format($unitsData[$uKey]) }}</td>
                            </tr>
                        @endif
                    @endforeach

                    @if(!$hasTroops)
                        <tr>
                            <td colspan="2" class="text-center text-gray-600 italic py-3">No troops currently stationed in village.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Production Rates & Capacities Summary -->
        <div class="tw-panel p-3">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                📊 Village Economy
            </h3>
            <table class="tw-table">
                <tbody>
                    <tr>
                        <td>Wood Production</td>
                        <td class="text-right font-bold text-[#8a5229]">+{{ number_format($village->wood_production_per_hour) }} / hour</td>
                    </tr>
                    <tr>
                        <td>Clay Production</td>
                        <td class="text-right font-bold text-[#c46835]">+{{ number_format($village->clay_production_per_hour) }} / hour</td>
                    </tr>
                    <tr>
                        <td>Iron Production</td>
                        <td class="text-right font-bold text-[#54606b]">+{{ number_format($village->iron_production_per_hour) }} / hour</td>
                    </tr>
                    <tr>
                        <td>Warehouse Storage Cap</td>
                        <td class="text-right font-bold">{{ number_format($village->storage_capacity) }} of each resource</td>
                    </tr>
                    <tr>
                        <td>Hideout Protection</td>
                        <td class="text-right font-bold text-green-800">{{ number_format($village->hide_capacity) }} protected</td>
                    </tr>
                    <tr>
                        <td>Free Farm Population</td>
                        <td class="text-right font-bold text-[#3b200b]">{{ number_format($village->free_population) }} available</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
