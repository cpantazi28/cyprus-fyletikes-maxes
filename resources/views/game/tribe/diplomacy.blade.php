@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>🤝 [{{ $tribe->tag }}] Diplomacy</span>
            </h2>
            <a href="{{ route('game.tribe.index') }}" class="tw-btn text-xs">
                &larr; Tribe Overview
            </a>
        </div>

        @if($isLeader)
        <!-- Add Diplomacy Form -->
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-xs text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2">
                Declare Diplomatic Status
            </h3>
            <form method="POST" action="{{ route('game.tribe.store-diplomacy') }}" class="flex flex-wrap items-end gap-2 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Target Tribe Tag:</label>
                    <input type="text" name="tag" required maxlength="8" placeholder="e.g. WAR" class="bg-white border border-[#7d5128] rounded px-2 py-1 uppercase font-bold focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Relation Type:</label>
                    <select name="type" class="bg-white border border-[#7d5128] rounded px-2 py-1 font-bold">
                        <option value="ally">🤝 Ally</option>
                        <option value="nap">🕊️ Non-Aggression Pact (NAP)</option>
                        <option value="enemy">⚔️ Enemy</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="tw-btn tw-btn-success py-1 px-3 font-bold">Save Relation</button>
                </div>
            </form>
        </div>
        @endif

        <!-- Relations Tables -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            
            <!-- Allies -->
            <div class="bg-[#f5ecc7] p-2.5 rounded border border-[#c9b183]">
                <h4 class="font-bold text-blue-900 border-b border-[#dfcf99] pb-1 mb-2">🤝 Allies ({{ $tribe->allies->count() }})</h4>
                <ul class="space-y-1">
                    @forelse($tribe->allies as $d)
                        <li class="font-bold text-blue-900">[{{ $d->targetTribe->tag }}] {{ $d->targetTribe->name }}</li>
                    @empty
                        <li class="text-gray-500 italic">No allies declared.</li>
                    @endforelse
                </ul>
            </div>

            <!-- NAPs -->
            <div class="bg-[#f5ecc7] p-2.5 rounded border border-[#c9b183]">
                <h4 class="font-bold text-purple-900 border-b border-[#dfcf99] pb-1 mb-2">🕊️ Non-Aggression ({{ $tribe->naps->count() }})</h4>
                <ul class="space-y-1">
                    @forelse($tribe->naps as $d)
                        <li class="font-bold text-purple-900">[{{ $d->targetTribe->tag }}] {{ $d->targetTribe->name }}</li>
                    @empty
                        <li class="text-gray-500 italic">No NAPs declared.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Enemies -->
            <div class="bg-[#f5ecc7] p-2.5 rounded border border-[#c9b183]">
                <h4 class="font-bold text-red-800 border-b border-[#dfcf99] pb-1 mb-2">⚔️ Enemies ({{ $tribe->enemies->count() }})</h4>
                <ul class="space-y-1">
                    @forelse($tribe->enemies as $d)
                        <li class="font-bold text-red-800">[{{ $d->targetTribe->tag }}] {{ $d->targetTribe->name }}</li>
                    @empty
                        <li class="text-gray-500 italic">No enemies declared.</li>
                    @endforelse
                </ul>
            </div>

        </div>

    </div>

</div>
@endsection
