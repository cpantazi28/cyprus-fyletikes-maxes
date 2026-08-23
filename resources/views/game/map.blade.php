@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        
        <!-- Map Controls & Navigation -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#c2a46c] pb-2 mb-3">
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-1.5">
                    <span>🗺️ World Map</span>
                </h2>
                <span class="text-xs bg-[#7c4c23] text-[#fff2cc] px-2 py-0.5 rounded font-mono font-bold">
                    Center: ({{ $centerX }}|{{ $centerY }})
                </span>
            </div>

            <!-- Coordinate Jump Form -->
            <form method="GET" action="{{ route('game.map') }}" class="flex items-center gap-2 text-xs">
                <span class="font-bold text-[#522f12]">Jump to:</span>
                <input type="number" name="x" value="{{ $centerX }}" min="0" max="999" class="w-14 bg-white border border-[#7d5128] rounded px-1.5 py-0.5 font-mono text-center focus:outline-none">
                <span>|</span>
                <input type="number" name="y" value="{{ $centerY }}" min="0" max="999" class="w-14 bg-white border border-[#7d5128] rounded px-1.5 py-0.5 font-mono text-center focus:outline-none">
                <button type="submit" class="tw-btn text-xs py-0.5 px-2">Go</button>
                <a href="{{ route('game.map', ['x' => $village->x, 'y' => $village->y]) }}" class="tw-btn text-xs py-0.5 px-2" title="Center on active village">
                    My Village
                </a>
            </form>
        </div>

        <!-- Directional Navigation Bar -->
        <div class="flex justify-center items-center gap-2 mb-3 text-xs">
            <a href="{{ route('game.map', ['x' => $centerX - 10, 'y' => $centerY]) }}" class="tw-btn py-0.5 px-2">⬅️ West</a>
            <a href="{{ route('game.map', ['x' => $centerX, 'y' => $centerY - 10]) }}" class="tw-btn py-0.5 px-2">⬆️ North</a>
            <a href="{{ route('game.map', ['x' => $centerX, 'y' => $centerY + 10]) }}" class="tw-btn py-0.5 px-2">⬇️ South</a>
            <a href="{{ route('game.map', ['x' => $centerX + 10, 'y' => $centerY]) }}" class="tw-btn py-0.5 px-2">➡️ East</a>
        </div>

        <!-- Interactive Map Grid Canvas -->
        <div class="overflow-x-auto p-2 bg-[#1b0e04] rounded border-2 border-[#522f12] shadow-inner flex justify-center">
            
            <div class="inline-block border border-[#3b200b] bg-[#345c22]">
                
                <!-- Coordinate X Header -->
                <div class="flex">
                    <div class="w-8 h-6 bg-[#2b1704] text-[10px] text-[#e6be44] font-mono font-bold flex items-center justify-center border-r border-b border-[#4d290e]">X\Y</div>
                    @for($x = $sector['min_x']; $x <= $sector['max_x']; $x++)
                        <div class="w-11 h-6 bg-[#2b1704] text-[10px] text-[#e6be44] font-mono font-bold flex items-center justify-center border-r border-b border-[#4d290e]">
                            {{ $x }}
                        </div>
                    @endfor
                </div>

                <!-- Grid Rows -->
                @foreach($sector['grid'] as $row)
                    @php $rowY = $row[0]['y'] ?? 0; @endphp
                    <div class="flex">
                        
                        <!-- Coordinate Y Axis -->
                        <div class="w-8 h-11 bg-[#2b1704] text-[10px] text-[#e6be44] font-mono font-bold flex items-center justify-center border-r border-b border-[#4d290e]">
                            {{ $rowY }}
                        </div>

                        <!-- Tiles -->
                        @foreach($row as $tile)
                            @php
                                $v = $tile['village'];
                                $bgClass = match($tile['terrain']) {
                                    'forest' => 'bg-[#254217]',
                                    'mountain' => 'bg-[#473b2b]',
                                    'lake' => 'bg-[#214859]',
                                    default => 'bg-[#3b6326]',
                                };

                                $borderClass = 'border-r border-b border-[#2d471d]/60';
                                if ($tile['x'] == $centerX && $tile['y'] == $centerY) {
                                    $borderClass = 'border-2 border-yellow-300 ring-1 ring-yellow-400 z-10';
                                }
                            @endphp

                            <div class="w-11 h-11 relative flex items-center justify-center {{ $bgClass }} {{ $borderClass }} hover:brightness-125 cursor-pointer transition-all"
                                 onclick='openVillageModal(@json($tile))'
                                 title="({{ $tile['x'] }}|{{ $tile['y'] }}) {{ $v ? $v['name'] . ' (' . $v['player'] . ')' : ucfirst($tile['terrain']) }}">
                                
                                @if($v)
                                    @php
                                        $vColor = match($v['relationship']) {
                                            'self' => 'bg-yellow-400 border-yellow-800 text-black',
                                            'tribe' => 'bg-blue-600 border-blue-950 text-white',
                                            'barbarian' => 'bg-gray-400 border-gray-800 text-black',
                                            default => 'bg-amber-700 border-amber-950 text-white',
                                        };
                                    @endphp
                                    <div class="w-7 h-7 rounded border-2 shadow flex flex-col items-center justify-center {{ $vColor }}">
                                        <span class="text-[11px] leading-none">🏰</span>
                                    </div>
                                @elseif($tile['terrain'] === 'forest')
                                    <span class="text-xs opacity-50">🌲</span>
                                @elseif($tile['terrain'] === 'mountain')
                                    <span class="text-xs opacity-50">⛰️</span>
                                @elseif($tile['terrain'] === 'lake')
                                    <span class="text-xs opacity-50">🌊</span>
                                @endif

                            </div>
                        @endforeach
                    </div>
                @endforeach

            </div>

        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-bold pt-3 border-t border-[#c2a46c] mt-3">
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-yellow-400 border border-yellow-800 inline-block"></span>
                <span>Your Village</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-blue-600 border border-blue-950 inline-block"></span>
                <span>Tribe Members</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-gray-400 border border-gray-800 inline-block"></span>
                <span>Barbarian / Abandoned</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-amber-700 border border-amber-950 inline-block"></span>
                <span>Other Players</span>
            </div>
        </div>

    </div>

</div>

<!-- Interactive Village Details Modal -->
<div id="village-modal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden">
    <div class="tw-panel p-4 max-w-sm w-full shadow-2xl relative animate-in fade-in zoom-in-95 duration-150">
        <button onclick="closeVillageModal()" class="absolute top-2 right-2 text-lg text-gray-700 hover:text-black font-bold cursor-pointer leading-none">&times;</button>
        
        <h3 id="modal-village-name" class="font-bold text-base text-[#3b200b] border-b border-[#c2a46c] pb-1 mb-2 tw-title">
            Village Details
        </h3>

        <div class="text-xs space-y-1.5 mb-4">
            <p>Coordinates: <strong id="modal-coords" class="font-mono text-[#7c4c23]"></strong> (<span id="modal-continent"></span>)</p>
            <p>Owner: <strong id="modal-player" class="text-[#3b200b]"></strong></p>
            <p id="modal-tribe-container">Tribe: <strong id="modal-tribe" class="text-blue-900"></strong></p>
            <p>Points: <strong id="modal-points"></strong></p>
            <p>Distance from active village: <strong id="modal-distance"></strong> fields</p>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-[#c2a46c]" id="modal-actions">
            <a id="btn-send-troops" href="#" class="tw-btn tw-btn-danger text-xs py-1.5 font-bold text-center">
                ⚔️ Send Troops
            </a>
            <a id="btn-send-resources" href="#" class="tw-btn tw-btn-success text-xs py-1.5 font-bold text-center">
                📦 Send Resources
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openVillageModal(tile) {
        if (!tile.village) {
            return;
        }

        const v = tile.village;
        document.getElementById('modal-village-name').innerText = v.name;
        document.getElementById('modal-coords').innerText = `${tile.x}|${tile.y}`;
        document.getElementById('modal-continent').innerText = tile.continent;
        document.getElementById('modal-player').innerText = v.player;
        
        const tribeCont = document.getElementById('modal-tribe-container');
        if (v.tribe) {
            tribeCont.style.display = 'block';
            document.getElementById('modal-tribe').innerText = `[${v.tribe}]`;
        } else {
            tribeCont.style.display = 'none';
        }

        document.getElementById('modal-points').innerText = `${v.points.toLocaleString()} pts`;
        document.getElementById('modal-distance').innerText = v.distance;

        // Configure Action Links
        document.getElementById('btn-send-troops').href = `{{ route('game.place') }}?x=${tile.x}&y=${tile.y}`;
        document.getElementById('btn-send-resources').href = `{{ route('game.building.show', 'market') }}?x=${tile.x}&y=${tile.y}`;

        document.getElementById('village-modal').classList.remove('hidden');
    }

    function closeVillageModal() {
        document.getElementById('village-modal').classList.add('hidden');
    }

    // Close on background click
    document.getElementById('village-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('village-modal')) {
            closeVillageModal();
        }
    });
</script>
@endpush
@endsection
