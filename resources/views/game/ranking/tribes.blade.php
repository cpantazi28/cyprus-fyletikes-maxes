@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>🏆 Tribe Leaderboard</span>
            </h2>
        </div>

        <div class="flex items-center gap-1 mb-3 text-xs">
            <a href="{{ route('game.ranking.players') }}" class="tw-btn py-1">Player Rankings</a>
            <a href="{{ route('game.ranking.tribes') }}" class="tw-btn py-1 tw-btn-success">Tribe Rankings</a>
        </div>

        @if($tribes->isEmpty())
            <p class="text-xs text-gray-600 italic py-4 text-center">No tribes founded yet.</p>
        @else
            <table class="tw-table">
                <thead>
                    <tr>
                        <th class="w-16">Rank</th>
                        <th>Tag</th>
                        <th>Tribe Name</th>
                        <th class="text-right">Total Points</th>
                        <th class="text-center w-24">Members</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tribes as $t)
                        @php
                            $rank = ($tribes->currentPage() - 1) * $tribes->perPage() + $loop->iteration;
                            $isMyTribe = Auth::user()->tribe?->id === $t->id;
                        @endphp
                        <tr class="{{ $isMyTribe ? 'bg-yellow-100 font-bold' : '' }}">
                            <td class="font-bold text-[#522f12]">{{ $rank }}</td>
                            <td class="font-bold text-blue-900">
                                [{{ $t->tag }}]
                            </td>
                            <td>
                                <strong>{{ $t->name }}</strong>
                                @if($isMyTribe) <span class="text-xs text-yellow-800">(Your Tribe)</span> @endif
                            </td>
                            <td class="text-right font-mono font-bold text-[#5a3416]">{{ number_format($t->points) }}</td>
                            <td class="text-center font-mono">{{ $t->members_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $tribes->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
