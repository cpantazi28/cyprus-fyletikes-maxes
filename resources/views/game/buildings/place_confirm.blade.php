@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <h2 class="text-lg font-bold text-[#3b200b] tw-title border-b border-[#c2a46c] pb-2 mb-3 flex items-center gap-2">
            <span>{{ $type === 'attack' ? '⚔️ Confirm Attack' : '🛡️ Confirm Support' }}</span>
        </h2>

        <form method="POST" action="{{ route('game.place.send') }}">
            @csrf
            <input type="hidden" name="target_village_id" value="{{ $targetVillage->id }}">
            <input type="hidden" name="type" value="{{ $type }}">

            @foreach($units as $uKey => $cnt)
                <input type="hidden" name="units[{{ $uKey }}]" value="{{ $cnt }}">
            @endforeach

            <!-- Movement Summary Table -->
            <table class="tw-table mb-4">
                <tbody>
                    <tr>
                        <td class="font-bold w-1/3">Target Village:</td>
                        <td>
                            <strong>{{ $targetVillage->name }}</strong> ({{ $targetVillage->coordinates }}) - {{ $targetVillage->continent }}
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold">Target Player:</td>
                        <td>
                            <strong>{{ $targetVillage->user->name ?? 'Barbarian' }}</strong>
                            @if($targetVillage->user?->tribe)
                                <span class="font-bold text-[#7c4c23]">[{{ $targetVillage->user->tribe->tag }}]</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold">Distance:</td>
                        <td>{{ $distance }} fields</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Travel Duration:</td>
                        <td class="font-mono font-bold">{{ gmdate('H:i:s', $durationSeconds) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Arrival Time:</td>
                        <td class="font-mono font-bold text-[#8a2908]">{{ $arrivesAt->format('H:i:s d/m/Y') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Units in Command Table -->
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                🪖 Units in this Command
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2 mb-4">
                @foreach($units as $uKey => $cnt)
                    @if($cnt > 0)
                        <div class="bg-[#f5ecc7] p-2 rounded border border-[#c9b183] text-center">
                            <span class="text-xs text-gray-700 font-semibold">{{ $unitConfigs[$uKey]['name'] }}</span>
                            <div class="font-mono font-bold text-base text-[#3b200b]">{{ number_format($cnt) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Catapult Target Selector if Catapults Sent -->
            @if(($units['catapult'] ?? 0) > 0 && $type === 'attack')
            <div class="bg-[#fcf5e2] p-3 rounded border border-[#dfcf99] mb-4 flex items-center gap-3 text-xs">
                <span class="font-bold text-[#4a290e]">🎯 Catapult Target Building:</span>
                <select name="catapult_target" class="bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none font-bold">
                    <option value="wall">Wall</option>
                    <option value="main">Headquarters</option>
                    <option value="barracks">Barracks</option>
                    <option value="stable">Stable</option>
                    <option value="garage">Workshop</option>
                    <option value="snob">Academy</option>
                    <option value="smith">Smithy</option>
                    <option value="farm">Farm</option>
                    <option value="storage">Warehouse</option>
                    <option value="wood">Timber Camp</option>
                    <option value="stone">Clay Pit</option>
                    <option value="iron">Iron Mine</option>
                </select>
            </div>
            @endif

            <div class="flex items-center justify-between pt-2 border-t border-[#c2a46c]">
                <a href="{{ route('game.place') }}" class="tw-btn text-xs">
                    &larr; Back to Rally Point
                </a>
                <button type="submit" class="tw-btn {{ $type === 'attack' ? 'tw-btn-danger' : 'tw-btn-success' }} text-sm py-1.5 px-6 font-bold">
                    {{ $type === 'attack' ? '⚔️ Launch Attack' : '🛡️ Dispatch Support' }}
                </button>
            </div>
        </form>

    </div>

</div>
@endsection
