@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-base font-bold text-[#3b200b] tw-title">{{ $report->title }}</h2>
                <span class="text-xs text-[#7c4c23] font-mono">{{ $report->created_at->format('H:i:s d/m/Y') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('game.reports.index') }}" class="tw-btn text-xs">
                    &larr; Back to Reports
                </a>
                <form method="POST" action="{{ route('game.reports.destroy', $report->id) }}" onsubmit="return confirm('Delete this report?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5">Delete</button>
                </form>
            </div>
        </div>

        @php
            $data = $report->data ?? [];
            $att = $data['attacker'] ?? null;
            $def = $data['defender'] ?? null;
            $isCombatReport = $att && $def;
        @endphp

        @if($isCombatReport)
            
            <!-- Morale & Luck Bar -->
            <div class="flex flex-wrap items-center justify-between gap-2 bg-[#f5ecc7] p-2.5 rounded border border-[#c9b183] text-xs font-semibold mb-3">
                <div>
                    <span>Morale: <strong class="text-green-900">{{ $data['morale'] ?? 100 }}%</strong></span>
                </div>
                <div class="flex items-center gap-2">
                    <span>Luck (Attacker):</span>
                    @php $luck = $data['luck'] ?? 0; @endphp
                    <span class="font-bold font-mono {{ $luck >= 0 ? 'text-green-800' : 'text-red-700' }}">
                        {{ $luck > 0 ? '+' : '' }}{{ $luck }}%
                    </span>
                </div>
                <div>
                    <span class="px-2 py-0.5 rounded font-bold {{ ($data['attacker_wins'] ?? false) ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900' }}">
                        {{ ($data['attacker_wins'] ?? false) ? 'Attacker Won' : 'Defender Won' }}
                    </span>
                </div>
            </div>

            <!-- Attacker Table -->
            <div class="mb-4">
                <h3 class="font-bold text-xs text-[#8a2424] bg-[#ebd6a7] p-1.5 rounded-t border border-[#c9b183] flex items-center justify-between">
                    <span>⚔️ Attacker: <strong>{{ $att['user_name'] }}</strong> from {{ $att['village_name'] }} ({{ $att['coordinates'] }})</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="tw-table">
                        <thead>
                            <tr>
                                <th class="w-20">Units</th>
                                @foreach($unitConfigs as $uK => $uC)
                                    @if(($att['units'][$uK] ?? 0) > 0 || ($att['casualties'][$uK] ?? 0) > 0)
                                        <th class="text-center text-[10px]">{{ $uC['name'] }}</th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-bold">Quantity:</td>
                                @foreach($unitConfigs as $uK => $uC)
                                    @if(($att['units'][$uK] ?? 0) > 0 || ($att['casualties'][$uK] ?? 0) > 0)
                                        <td class="text-center font-mono font-bold text-xs">{{ $att['units'][$uK] ?? 0 }}</td>
                                    @endif
                                @endforeach
                            </tr>
                            <tr>
                                <td class="font-bold text-red-800">Losses:</td>
                                @foreach($unitConfigs as $uK => $uC)
                                    @if(($att['units'][$uK] ?? 0) > 0 || ($att['casualties'][$uK] ?? 0) > 0)
                                        <td class="text-center font-mono font-bold text-xs text-red-700">{{ $att['casualties'][$uK] ?? 0 }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Defender Table -->
            <div class="mb-4">
                <h3 class="font-bold text-xs text-[#355e1e] bg-[#ebd6a7] p-1.5 rounded-t border border-[#c9b183] flex items-center justify-between">
                    <span>🛡️ Defender: <strong>{{ $def['user_name'] }}</strong> in {{ $def['village_name'] }} ({{ $def['coordinates'] }})</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="tw-table">
                        <thead>
                            <tr>
                                <th class="w-20">Units</th>
                                @php $hasDefUnits = false; @endphp
                                @foreach($unitConfigs as $uK => $uC)
                                    @if(($def['units'][$uK] ?? 0) > 0 || ($def['casualties'][$uK] ?? 0) > 0)
                                        @php $hasDefUnits = true; @endphp
                                        <th class="text-center text-[10px]">{{ $uC['name'] }}</th>
                                    @endif
                                @endforeach
                                @if(!$hasDefUnits)
                                    <th class="text-center text-xs">None</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if($hasDefUnits)
                                <tr>
                                    <td class="font-bold">Quantity:</td>
                                    @foreach($unitConfigs as $uK => $uC)
                                        @if(($def['units'][$uK] ?? 0) > 0 || ($def['casualties'][$uK] ?? 0) > 0)
                                            <td class="text-center font-mono font-bold text-xs">{{ $def['units'][$uK] ?? 0 }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                                <tr>
                                    <td class="font-bold text-red-800">Losses:</td>
                                    @foreach($unitConfigs as $uK => $uC)
                                        @if(($def['units'][$uK] ?? 0) > 0 || ($def['casualties'][$uK] ?? 0) > 0)
                                            <td class="text-center font-mono font-bold text-xs text-red-700">{{ $def['casualties'][$uK] ?? 0 }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @else
                                <tr>
                                    <td colspan="2" class="text-center text-gray-600 italic py-2">No defending troops were stationed in the village.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Haul / Plunder Section -->
            @if(!empty($data['haul']) && (($data['haul']['wood'] ?? 0) > 0 || ($data['haul']['clay'] ?? 0) > 0 || ($data['haul']['iron'] ?? 0) > 0))
            <div class="bg-[#f5ecc7] p-2.5 rounded border border-[#c9b183] text-xs mb-3 flex items-center justify-between">
                <span class="font-bold text-[#4a290e]">🎒 Plunder / Haul:</span>
                <div class="flex items-center gap-3 font-semibold">
                    <span class="text-[#8a5229]">🌲 {{ number_format($data['haul']['wood'] ?? 0) }} Wood</span>
                    <span class="text-[#c46835]">🧱 {{ number_format($data['haul']['clay'] ?? 0) }} Clay</span>
                    <span class="text-[#54606b]">⛏️ {{ number_format($data['haul']['iron'] ?? 0) }} Iron</span>
                    <span class="font-bold text-[#381e08]">Total: {{ number_format(($data['haul']['wood'] ?? 0) + ($data['haul']['clay'] ?? 0) + ($data['haul']['iron'] ?? 0)) }}</span>
                </div>
            </div>
            @endif

            <!-- Damage & Conquest Notifications -->
            @if(!empty($data['wall_damage']))
                <div class="bg-amber-100 border border-amber-300 text-amber-900 p-2 rounded text-xs mb-2 font-bold flex items-center gap-1.5">
                    <span>🧱 Wall damaged by Rams from level <strong>{{ $data['wall_damage']['from'] }}</strong> to level <strong>{{ $data['wall_damage']['to'] }}</strong>.</span>
                </div>
            @endif

            @if(!empty($data['building_damage']))
                <div class="bg-amber-100 border border-amber-300 text-amber-900 p-2 rounded text-xs mb-2 font-bold flex items-center gap-1.5">
                    <span>🪵 {{ config("game.buildings.{$data['building_damage']['building']}.name") }} damaged by Catapults from level <strong>{{ $data['building_damage']['from'] }}</strong> to level <strong>{{ $data['building_damage']['to'] }}</strong>.</span>
                </div>
            @endif

            @if(!empty($data['loyalty_change']))
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-950 p-2.5 rounded text-xs mb-2 font-bold">
                    @if($data['loyalty_change']['conquered'] ?? false)
                        <div class="text-green-800 text-sm">👑 The village has been CONQUERED! Ownership has been transferred.</div>
                    @else
                        <div>👑 Loyalty reduced from <strong>{{ number_format($data['loyalty_change']['from'], 1) }}</strong> to <strong>{{ number_format($data['loyalty_change']['to'], 1) }}</strong>.</div>
                    @endif
                </div>
            @endif

            <!-- Scout Espionage Intel -->
            @if(!empty($data['scout_intel']))
            <div class="bg-[#f0e8d0] p-3 rounded border border-[#caa552] text-xs mt-3">
                <h4 class="font-bold text-xs text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2 flex items-center gap-1">
                    <span>🔍 Espionage Report (Scouting Intelligence)</span>
                </h4>
                
                @if(!empty($data['scout_intel']['resources']))
                    <p class="mb-2"><strong>Spied Resources in Village:</strong> 🌲 {{ number_format($data['scout_intel']['resources']['wood']) }} Wood, 🧱 {{ number_format($data['scout_intel']['resources']['clay']) }} Clay, ⛏️ {{ number_format($data['scout_intel']['resources']['iron']) }} Iron</p>
                @endif

                @if(!empty($data['scout_intel']['buildings']))
                    <p class="font-bold mb-1">Spied Buildings:</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                        @foreach($data['scout_intel']['buildings'] as $bKey => $bLvl)
                            @if($bLvl > 0)
                                <div class="bg-white p-1 rounded border border-[#dfcf99]">
                                    {{ config("game.buildings.{$bKey}.name") }}: <strong>Lv {{ $bLvl }}</strong>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

        @else
            <!-- Generic / Support / System Report -->
            <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] text-xs">
                <pre class="whitespace-pre-wrap font-sans">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

    </div>

</div>
@endsection
