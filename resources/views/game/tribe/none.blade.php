@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <h2 class="text-lg font-bold text-[#3b200b] tw-title border-b border-[#c2a46c] pb-2 mb-3 flex items-center gap-2">
            <span>🛡️ Tribe / Alliance</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Create Tribe Form -->
            <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183]">
                <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-3">
                    👑 Found a New Tribe
                </h3>

                <form method="POST" action="{{ route('game.tribe.store') }}" class="space-y-2 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-[#522f12] mb-1">Tribe Name:</label>
                        <input type="text" name="name" required minlength="3" maxlength="50" placeholder="e.g. Knights of the Realm" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none font-bold">
                    </div>
                    <div>
                        <label class="block font-bold text-[#522f12] mb-1">Tribe Tag (Abbreviation, 2-8 chars):</label>
                        <input type="text" name="tag" required minlength="2" maxlength="8" placeholder="e.g. KOTR" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 uppercase font-bold focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-[#522f12] mb-1">Description / Profile:</label>
                        <textarea name="description" rows="3" placeholder="Brief introduction of your tribe..." class="w-full bg-white border border-[#7d5128] rounded p-2 focus:outline-none"></textarea>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="tw-btn tw-btn-success text-xs py-1.5 px-4 font-bold">
                            🛡️ Found Tribe
                        </button>
                    </div>
                </form>
            </div>

            <!-- Top Ranking Tribes -->
            <div>
                <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                    🏆 Top Tribes in the World
                </h3>

                @if($topTribes->isEmpty())
                    <p class="text-xs text-gray-600 italic py-2">No tribes currently founded.</p>
                @else
                    <table class="tw-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Tag</th>
                                <th>Name</th>
                                <th>Points</th>
                                <th>Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topTribes as $t)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="font-bold text-blue-900">[{{ $t->tag }}]</td>
                                    <td>{{ $t->name }}</td>
                                    <td class="font-mono font-bold text-[#5a3416]">{{ number_format($t->points) }}</td>
                                    <td>{{ $t->member_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
