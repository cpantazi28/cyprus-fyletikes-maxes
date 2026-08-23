@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>⚔️ Battle Simulator</span>
                </h2>
                <p class="text-xs text-[#6e431f]">Simulate combat scenarios against various troop compositions, wall levels, and luck factors.</p>
            </div>
            <div>
                <a href="{{ route('game.place') }}" class="tw-btn text-xs">
                    &larr; Rally Point
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('game.simulator') }}">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                
                <!-- Attacker Composition -->
                <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183]">
                    <h3 class="font-bold text-sm text-[#8a2424] border-b border-[#dfcf99] pb-1 mb-2">
                        ⚔️ Attacker Army
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($unitConfigs as $uK => $uC)
                            <div>
                                <label class="block text-[11px] font-bold text-[#3b200b]">{{ $uC['name'] }}:</label>
                                <input type="number" name="att[{{ $uK }}]" value="{{ $attackerInput[$uK] ?? 0 }}" min="0" class="w-full bg-white border border-[#7d5128] rounded px-1.5 py-0.5 text-xs text-right font-mono focus:outline-none">
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Defender Composition -->
                <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183]">
                    <h3 class="font-bold text-sm text-[#355e1e] border-b border-[#dfcf99] pb-1 mb-2">
                        🛡️ Defender Army & Fortifications
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3">
                        @foreach($unitConfigs as $uK => $uC)
                            <div>
                                <label class="block text-[11px] font-bold text-[#3b200b]">{{ $uC['name'] }}:</label>
                                <input type="number" name="def[{{ $uK }}]" value="{{ $defenderInput[$uK] ?? 0 }}" min="0" class="w-full bg-white border border-[#7d5128] rounded px-1.5 py-0.5 text-xs text-right font-mono focus:outline-none">
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-[#dfcf99] text-xs">
                        <div>
                            <label class="block font-bold text-[#522f12]">Wall Level:</label>
                            <input type="number" name="wall" value="{{ $wallLevel }}" min="0" max="20" class="w-full bg-white border border-[#7d5128] rounded px-1 py-0.5 font-mono text-center focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#522f12]">Luck (%):</label>
                            <input type="number" name="luck" value="{{ $luck }}" min="-25" max="25" step="0.5" class="w-full bg-white border border-[#7d5128] rounded px-1 py-0.5 font-mono text-center focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-[#522f12]">Morale (%):</label>
                            <input type="number" name="morale" value="{{ $morale }}" min="30" max="100" class="w-full bg-white border border-[#7d5128] rounded px-1 py-0.5 font-mono text-center focus:outline-none">
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex justify-center mb-4">
                <button type="submit" name="simulate" value="1" class="tw-btn tw-btn-success text-sm py-2 px-8 font-bold">
                    ⚔️ Run Battle Simulation
                </button>
            </div>
        </form>

        <!-- Simulation Results -->
        @if($results)
        <div class="bg-white p-3 rounded border-2 border-[#7d5128]">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-3 flex items-center justify-between">
                <span>📊 Simulation Outcome</span>
                <span class="text-xs px-2 py-0.5 rounded font-bold {{ $results['attacker_wins'] ? 'bg-green-100 text-green-900 border border-green-300' : 'bg-red-100 text-red-900 border border-red-300' }}">
                    {{ $results['attacker_wins'] ? 'Attacker Victorious' : 'Defender Victorious' }}
                </span>
            </h3>

            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Army</th>
                        @foreach($unitConfigs as $uK => $uC)
                            <th class="text-center text-[10px]">{{ $uC['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-bold text-red-800">Attacker Troops</td>
                        @foreach($unitConfigs as $uK => $uC)
                            <td class="text-center font-mono text-xs">{{ $attackerInput[$uK] ?? 0 }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="font-bold text-red-800">Attacker Losses</td>
                        @foreach($unitConfigs as $uK => $uC)
                            <td class="text-center font-mono text-xs text-red-700 font-bold">{{ $results['att_losses'][$uK] ?? 0 }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="font-bold text-green-900">Defender Troops</td>
                        @foreach($unitConfigs as $uK => $uC)
                            <td class="text-center font-mono text-xs">{{ $defenderInput[$uK] ?? 0 }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="font-bold text-green-900">Defender Losses</td>
                        @foreach($unitConfigs as $uK => $uC)
                            <td class="text-center font-mono text-xs text-red-700 font-bold">{{ $results['def_losses'][$uK] ?? 0 }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

    </div>

</div>
@endsection
