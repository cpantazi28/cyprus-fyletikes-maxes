@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-2">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>⚒️ {{ $buildingConfig['name'] }} (Level {{ $currentLevel }})</span>
                </h2>
                <p class="text-xs text-[#6e431f]">{{ $buildingConfig['description'] }}</p>
            </div>
            <div class="text-xs">
                <span class="text-[#7c4c23] font-bold">Forge status:</span>
                <span class="bg-[#f0e2b6] px-2 py-0.5 rounded font-mono font-bold text-green-800">Operational</span>
            </div>
        </div>

        <!-- Tech Research Table -->
        <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
            🔬 Military Research & Unlocks
        </h3>

        <table class="tw-table">
            <thead>
                <tr>
                    <th>Unit / Weapon Tech</th>
                    <th>Smithy Req</th>
                    <th>Status</th>
                    <th>Military Stats</th>
                </tr>
            </thead>
            <tbody>
                @foreach(config('game.units') as $uKey => $uCfg)
                    @php
                        $smithReq = $uCfg['requirements']['smith'] ?? 0;
                        $isUnlocked = $currentLevel >= $smithReq;
                    @endphp
                    <tr>
                        <td class="font-bold text-[#3b200b]">{{ $uCfg['name'] }}</td>
                        <td>
                            @if($smithReq > 0)
                                <span class="{{ $isUnlocked ? 'text-green-800' : 'text-red-700 font-bold' }}">
                                    Smithy Lv {{ $smithReq }}
                                </span>
                            @else
                                <span class="text-gray-500 text-xs">None (Basic)</span>
                            @endif
                        </td>
                        <td>
                            @if($isUnlocked)
                                <span class="bg-green-100 text-green-800 border border-green-300 text-xs px-2 py-0.5 rounded font-bold">
                                    ✓ Researched
                                </span>
                            @else
                                <span class="bg-red-50 text-red-700 border border-red-200 text-xs px-2 py-0.5 rounded font-bold">
                                    🔒 Upgrade Smithy
                                </span>
                            @endif
                        </td>
                        <td class="text-xs text-[#522f12]">
                            ⚔️ Attack: <strong>{{ $uCfg['attack'] }}</strong> &bull;
                            🛡️ Defense: Inf <strong>{{ $uCfg['def_infantry'] }}</strong> / Cav <strong>{{ $uCfg['def_cavalry'] }}</strong> / Arc <strong>{{ $uCfg['def_archer'] }}</strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</div>
@endsection
