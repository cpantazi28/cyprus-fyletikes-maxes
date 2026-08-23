@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-2">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>👑 {{ $buildingConfig['name'] }} (Level {{ $currentLevel }})</span>
                </h2>
                <p class="text-xs text-[#6e431f]">{{ $buildingConfig['description'] }}</p>
            </div>
            <div class="text-xs">
                <span class="text-[#7c4c23] font-bold">Gold Coins Minted:</span>
                <span class="bg-[#f0e2b6] px-2 py-0.5 rounded font-mono font-bold text-yellow-700">
                    🪙 {{ $village->coins_minted }} coins
                </span>
            </div>
        </div>

        <!-- Gold Coin Minting Section -->
        @php
            $coinCost = config('game.buildings.snob.coin_cost', ['wood' => 28000, 'clay' => 30000, 'iron' => 25000]);
            $canAffordCoin = $village->wood >= $coinCost['wood'] && $village->clay >= $coinCost['clay'] && $village->iron >= $coinCost['iron'];
        @endphp

        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] mb-1 flex items-center gap-1.5">
                <span>🪙 Mint Gold Coins</span>
            </h3>
            <p class="text-xs text-[#522f12] mb-3">
                Minting gold coins allows you to educate Noblemen to conquer enemy villages.
            </p>

            <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-2 rounded border border-[#dfcf99]">
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span>Cost per coin:</span>
                    <span class="{{ $village->wood < $coinCost['wood'] ? 'text-red-700 font-bold' : 'text-[#8a5229]' }}">
                        🌲 {{ number_format($coinCost['wood']) }}
                    </span>
                    <span class="{{ $village->clay < $coinCost['clay'] ? 'text-red-700 font-bold' : 'text-[#c46835]' }}">
                        🧱 {{ number_format($coinCost['clay']) }}
                    </span>
                    <span class="{{ $village->iron < $coinCost['iron'] ? 'text-red-700 font-bold' : 'text-[#54606b]' }}">
                        ⛏️ {{ number_format($coinCost['iron']) }}
                    </span>
                </div>

                <form method="POST" action="{{ route('game.building.mint-coin') }}">
                    @csrf
                    <button type="submit" {{ !$canAffordCoin ? 'disabled' : '' }} class="tw-btn tw-btn-success text-xs py-1 px-3">
                        🪙 Mint Gold Coin
                    </button>
                </form>
            </div>
        </div>

        <!-- Nobleman Recruitment Section -->
        @php
            $snobCfg = config('game.units.snob');
            $costTime = $recruitmentService->calculateUnitCostAndTime($village, 'snob');
            $inVillage = $village->units?->snob ?? 0;
            $canAffordSnob = $village->wood >= $snobCfg['wood'] && $village->clay >= $snobCfg['clay'] && $village->iron >= $snobCfg['iron'] && $village->free_population >= $snobCfg['pop'];
        @endphp

        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183]">
            <h3 class="font-bold text-sm text-[#4a290e] mb-1 flex items-center gap-1.5">
                <span>👑 Educate Nobleman</span>
            </h3>
            <p class="text-xs text-[#522f12] mb-3">
                A Nobleman reduces enemy village loyalty by 20-35 points upon successful attacks until the village is conquered.
            </p>

            <table class="tw-table mb-3">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>In Village</th>
                        <th>Cost</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="font-bold text-[#3b200b]">{{ $snobCfg['name'] }}</div>
                            <div class="text-[11px] text-[#6e431f]">{{ $snobCfg['description'] }}</div>
                        </td>
                        <td class="font-mono font-bold text-[#5a3416]">{{ number_format($inVillage) }}</td>
                        <td>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-[#8a5229]">🌲 {{ number_format($snobCfg['wood']) }}</span>
                                <span class="text-[#c46835]">🧱 {{ number_format($snobCfg['clay']) }}</span>
                                <span class="text-[#54606b]">⛏️ {{ number_format($snobCfg['iron']) }}</span>
                                <span class="text-[#3b200b]">👥 {{ $snobCfg['pop'] }}</span>
                            </div>
                        </td>
                        <td class="font-mono text-xs text-[#522f12]">⏱️ {{ gmdate('H:i:s', $costTime['seconds_per_unit']) }}</td>
                        <td>
                            <form method="POST" action="{{ route('game.building.recruit', 'snob') }}">
                                @csrf
                                <input type="hidden" name="units[snob]" value="1">
                                <button type="submit" {{ !$canAffordSnob ? 'disabled' : '' }} class="tw-btn tw-btn-success text-xs py-1 px-3">
                                    Educate Nobleman
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
