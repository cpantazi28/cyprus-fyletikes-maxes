@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>{{ $buildingConfig['name'] }} (Level {{ $currentLevel }})</span>
                </h2>
                <p class="text-xs text-[#6e431f]">{{ $buildingConfig['description'] }}</p>
            </div>
            <div>
                <a href="{{ route('game.building.show', 'main') }}" class="tw-btn text-xs">
                    &larr; Headquarters
                </a>
            </div>
        </div>

        <!-- Current Production / Capacity details -->
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2">
                📊 Current Level Stats
            </h3>
            
            <div class="text-xs space-y-1">
                @if(isset($buildingConfig['production']))
                    <p>Current Production: <strong class="text-green-900">+{{ $village->getBuildingLevel($building) > 0 ? $buildingConfig['production'][$currentLevel] : 5 }}</strong> per hour (x{{ config('game.world.speed') }} world speed)</p>
                    @if($currentLevel < $buildingConfig['max_level'])
                        <p class="text-gray-700">Next Level ({{ $currentLevel + 1 }}): <strong class="text-green-800">+{{ $buildingConfig['production'][$currentLevel + 1] }}</strong> per hour</p>
                    @endif
                @elseif(isset($buildingConfig['capacities']))
                    <p>Current Capacity: <strong class="text-[#3b200b]">{{ number_format($buildingConfig['capacities'][$currentLevel] ?? 0) }}</strong></p>
                    @if($currentLevel < $buildingConfig['max_level'])
                        <p class="text-gray-700">Next Level ({{ $currentLevel + 1 }}): <strong>{{ number_format($buildingConfig['capacities'][$currentLevel + 1] ?? 0) }}</strong></p>
                    @endif
                @elseif($building === 'wall')
                    @php
                        $defBonus = config("game.buildings.wall.defense_bonus.{$currentLevel}", 0);
                        $baseDef = config("game.buildings.wall.base_defense.{$currentLevel}", 20);
                    @endphp
                    <p>Defense Bonus: <strong class="text-green-800">+{{ $defBonus }}%</strong></p>
                    <p>Base Village Defense: <strong class="text-[#3b200b]">{{ $baseDef }} points</strong></p>
                @endif
            </div>
        </div>

        <!-- Quick Upgrade Trigger -->
        @php
            $targetLvl = $buildingService->getTargetLevel($village, $building);
            $check = $buildingService->canUpgrade($village, $building);
            $cost = $check['cost'] ?? null;
        @endphp

        <div class="bg-white p-3 rounded border border-[#dfcf99]">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2">
                🏗️ Upgrade {{ $buildingConfig['name'] }} to Level {{ $targetLvl }}
            </h3>

            @if($cost)
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3 font-semibold">
                        <span class="{{ $village->wood < $cost['wood'] ? 'text-red-700 font-bold' : 'text-[#8a5229]' }}">
                            🌲 {{ number_format($cost['wood']) }}
                        </span>
                        <span class="{{ $village->clay < $cost['clay'] ? 'text-red-700 font-bold' : 'text-[#c46835]' }}">
                            🧱 {{ number_format($cost['clay']) }}
                        </span>
                        <span class="{{ $village->iron < $cost['iron'] ? 'text-red-700 font-bold' : 'text-[#54606b]' }}">
                            ⛏️ {{ number_format($cost['iron']) }}
                        </span>
                        <span class="{{ $village->free_population < $cost['pop'] ? 'text-red-700 font-bold' : 'text-[#3b200b]' }}">
                            👥 {{ $cost['pop'] }}
                        </span>
                        <span class="font-mono text-[#522f12]">
                            ⏱️ {{ gmdate('H:i:s', $cost['build_time_seconds']) }}
                        </span>
                    </div>

                    @if($check['allowed'])
                        <form method="POST" action="{{ route('game.building.upgrade', $building) }}">
                            @csrf
                            <button type="submit" class="tw-btn tw-btn-success text-xs py-1 px-3">
                                Upgrade to Level {{ $cost['level'] }}
                            </button>
                        </form>
                    @else
                        <button disabled class="tw-btn text-xs py-1 px-3" title="{{ $check['reason'] }}">
                            {{ $check['reason'] }}
                        </button>
                    @endif
                </div>
            @else
                <p class="text-xs text-gray-600 italic">{{ $check['reason'] ?? 'Maximum level reached.' }}</p>
            @endif
        </div>

    </div>

</div>
@endsection
