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

        <!-- Village Graphic Map Canvas Grid -->
        <div class="relative w-full rounded overflow-hidden border border-[#522f12] my-2 bg-[#2d4d1f] shadow-inner" style="min-height: 380px; background-image: radial-gradient(circle at 50% 50%, #477832 0%, #203b14 100%);">
            
            <!-- Atmospheric Village Ground Elements -->
            <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(#d5c28a 1.5px, transparent 1.5px); background-size: 24px 24px;"></div>

            <!-- Village Center Visual Layout -->
            <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">

                <!-- 1. Headquarters -->
                <a href="{{ route('game.building.show', 'main') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🏛️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Headquarters</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->main }}</span>
                </a>

                <!-- 2. Barracks -->
                @if($village->barracks > 0)
                <a href="{{ route('game.building.show', 'barracks') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">⚔️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Barracks</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->barracks }}</span>
                </a>
                @endif

                <!-- 3. Stable -->
                @if($village->stable > 0)
                <a href="{{ route('game.building.show', 'stable') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🐎</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Stable</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->stable }}</span>
                </a>
                @endif

                <!-- 4. Workshop -->
                @if($village->garage > 0)
                <a href="{{ route('game.building.show', 'garage') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🪵</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Workshop</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->garage }}</span>
                </a>
                @endif

                <!-- 5. Academy -->
                @if($village->snob > 0)
                <a href="{{ route('game.building.show', 'snob') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">👑</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Academy</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->snob }}</span>
                </a>
                @endif

                <!-- 6. Smithy -->
                @if($village->smith > 0)
                <a href="{{ route('game.building.show', 'smith') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">⚒️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Smithy</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->smith }}</span>
                </a>
                @endif

                <!-- 7. Rally Point -->
                <a href="{{ route('game.place') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🚩</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Rally Point</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->place }}</span>
                </a>

                <!-- 8. Market -->
                @if($village->market > 0)
                <a href="{{ route('game.building.show', 'market') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">⚖️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Market</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->market }}</span>
                </a>
                @endif

                <!-- 9. Timber Camp -->
                <a href="{{ route('game.building.show', 'wood') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🌲</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Timber Camp</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->wood_level }}</span>
                </a>

                <!-- 10. Clay Pit -->
                <a href="{{ route('game.building.show', 'stone') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🧱</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Clay Pit</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->stone_level }}</span>
                </a>

                <!-- 11. Iron Mine -->
                <a href="{{ route('game.building.show', 'iron') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">⛏️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Iron Mine</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->iron_level }}</span>
                </a>

                <!-- 12. Farm -->
                <a href="{{ route('game.building.show', 'farm') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🌾</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Farm</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->farm_level }}</span>
                </a>

                <!-- 13. Warehouse -->
                <a href="{{ route('game.building.show', 'storage') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">📦</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Warehouse</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->storage_level }}</span>
                </a>

                <!-- 14. Hideout -->
                <a href="{{ route('game.building.show', 'hide') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🕳️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Hideout</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->hide_level }}</span>
                </a>

                <!-- 15. Wall -->
                @if($village->wall_level > 0)
                <a href="{{ route('game.building.show', 'wall') }}" class="group relative flex flex-col items-center justify-center p-3 rounded bg-[#5a3416]/85 hover:bg-[#7c4c23] border-2 border-[#caa552] text-[#fff2cc] shadow-md transition-transform hover:-translate-y-0.5">
                    <span class="text-3xl mb-1">🛡️</span>
                    <span class="text-xs font-bold text-center group-hover:text-yellow-200">Wall</span>
                    <span class="text-[11px] bg-[#361c07] px-1.5 py-0.2 rounded mt-1 font-bold text-yellow-400">Lv {{ $village->wall_level }}</span>
                </a>
                @endif

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
