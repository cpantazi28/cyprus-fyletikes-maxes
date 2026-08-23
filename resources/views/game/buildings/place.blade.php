@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <!-- Rally Point Main Panel -->
    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>🚩 Rally Point</span>
                </h2>
                <p class="text-xs text-[#6e431f]">Command your armies, issue attack or support orders, and monitor stationed troops.</p>
            </div>
            <div>
                <a href="{{ route('game.simulator') }}" class="tw-btn text-xs font-bold">
                    ⚔️ Battle Simulator
                </a>
            </div>
        </div>

        <!-- Section 1: Send Troops Command Form -->
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-3 flex items-center justify-between">
                <span>⚔️ Issue Troop Movement Command</span>
                <button type="button" onclick="fillAllUnits()" class="text-xs text-[#7c4c23] hover:underline cursor-pointer font-bold">
                    [Select All Units]
                </button>
            </h3>

            <form method="POST" action="{{ route('game.place.confirm') }}" id="send-troops-form">
                @csrf
                
                <!-- Units Input Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 mb-3">
                    @foreach($units as $uKey => $uCfg)
                        @php $available = $villageUnits->{$uKey} ?? 0; @endphp
                        <div class="bg-white p-2 rounded border border-[#dfcf99] flex flex-col items-center">
                            <span class="font-bold text-xs text-[#3b200b]">{{ $uCfg['name'] }}</span>
                            <div class="flex items-center gap-1 my-1">
                                <input type="number" name="units[{{ $uKey }}]" min="0" max="{{ $available }}" value="0" id="place-unit-{{ $uKey }}" class="w-16 bg-[#faf6ea] border border-[#7d5128] rounded px-1 py-0.5 text-xs text-center font-mono focus:outline-none">
                            </div>
                            <button type="button" onclick="document.getElementById('place-unit-{{ $uKey }}').value = {{ $available }}" class="text-[10px] text-[#7c4c23] hover:underline font-semibold cursor-pointer">
                                Max: {{ $available }}
                            </button>
                        </div>
                    @endforeach
                </div>

                <!-- Coordinates & Action Buttons -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-3 rounded border border-[#dfcf99]">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-bold text-[#4a290e]">Target Coordinates:</span>
                        <div class="flex items-center gap-1">
                            <span>X:</span>
                            <input type="number" name="x" value="{{ $targetX ?? '' }}" required min="0" max="999" placeholder="500" class="w-16 bg-[#faf6ea] border border-[#7d5128] rounded px-2 py-1 text-xs font-mono font-bold focus:outline-none">
                            <span>|</span>
                            <span>Y:</span>
                            <input type="number" name="y" value="{{ $targetY ?? '' }}" required min="0" max="999" placeholder="500" class="w-16 bg-[#faf6ea] border border-[#7d5128] rounded px-2 py-1 text-xs font-mono font-bold focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" name="type" value="attack" class="tw-btn tw-btn-danger text-sm py-1.5 px-4 font-bold">
                            ⚔️ Attack
                        </button>
                        <button type="submit" name="type" value="support" class="tw-btn tw-btn-success text-sm py-1.5 px-4 font-bold">
                            🛡️ Support
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Section 2: Outgoing Troop Movements -->
        <div class="mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                🛡️ Outgoing Movements ({{ $outgoingMovements->count() }})
            </h3>

            @if($outgoingMovements->isEmpty())
                <p class="text-xs text-gray-600 italic py-2">No outgoing movements.</p>
            @else
                <table class="tw-table">
                    <thead>
                        <tr>
                            <th>Command</th>
                            <th>Target Village</th>
                            <th>Arrival In</th>
                            <th>Arrival Time</th>
                            <th>Units</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outgoingMovements as $mov)
                            <tr>
                                <td class="font-bold">
                                    {{ ucfirst($mov->type) }}
                                </td>
                                <td>
                                    {{ $mov->targetVillage->name }} ({{ $mov->targetVillage->coordinates }})
                                </td>
                                <td class="font-mono font-bold text-[#8a2908]" data-countdown="{{ $mov->remaining_seconds }}" data-autorefresh="true">
                                    {{ gmdate('H:i:s', $mov->remaining_seconds) }}
                                </td>
                                <td>{{ $mov->arrives_at->format('H:i:s d/m') }}</td>
                                <td class="text-xs">
                                    @foreach($mov->units ?? [] as $uK => $cnt)
                                        @if($cnt > 0)
                                            <span class="inline-block mr-1"><strong>{{ $cnt }}</strong> {{ config("game.units.{$uK}.name") }}</span>
                                        @endif
                                    @endforeach
                                </td>
                                <td>
                                    @if($mov->is_cancelable)
                                        <form method="POST" action="{{ route('game.place.cancel', $mov->id) }}">
                                            @csrf
                                            <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5 px-2">Cancel</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500 italic">En route</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Section 3: Stationed Foreign Support Troops -->
        @if($stationedSupports->isNotEmpty())
        <div class="mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                🛡️ Supporting Troops Stationed in Village
            </h3>
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Origin Village</th>
                        <th>Owner</th>
                        <th>Units</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stationedSupports as $sup)
                        <tr>
                            <td class="font-bold">{{ $sup->homeVillage->name }} ({{ $sup->homeVillage->coordinates }})</td>
                            <td>{{ $sup->homeVillage->user->name ?? 'Player' }}</td>
                            <td class="text-xs">
                                @foreach($sup->units ?? [] as $uK => $cnt)
                                    @if($cnt > 0)
                                        <span class="inline-block mr-1"><strong>{{ $cnt }}</strong> {{ config("game.units.{$uK}.name") }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <form method="POST" action="{{ route('game.place.send-back', $sup->id) }}">
                                    @csrf
                                    <button type="submit" class="tw-btn text-xs py-0.5 px-2">Send Back</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

</div>

@push('scripts')
<script>
    function fillAllUnits() {
        @foreach($units as $uKey => $uCfg)
            @php $available = $villageUnits->{$uKey} ?? 0; @endphp
            const el_{{ $uKey }} = document.getElementById('place-unit-{{ $uKey }}');
            if (el_{{ $uKey }}) el_{{ $uKey }}.value = {{ $available }};
        @endforeach
    }
</script>
@endpush
@endsection
