@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>🏆 World Leaderboard</span>
            </h2>
        </div>

        <!-- Ranking Tabs -->
        <div class="flex items-center gap-1 mb-3 text-xs">
            <a href="{{ route('game.ranking.players') }}" class="tw-btn py-1 tw-btn-success">Player Rankings</a>
            <a href="{{ route('game.ranking.tribes') }}" class="tw-btn py-1">Tribe Rankings</a>
        </div>

        <!-- Leaderboard Table -->
        <table class="tw-table">
            <thead>
                <tr>
                    <th class="w-16">Rank</th>
                    <th>Player</th>
                    <th>Tribe</th>
                    <th class="text-right">Points</th>
                    <th class="text-center w-24">Villages</th>
                </tr>
            </thead>
            <tbody>
                @foreach($players as $index => $p)
                    @php
                        $rank = ($players->currentPage() - 1) * $players->perPage() + $loop->iteration;
                        $isMe = $p->id === Auth::id();
                    @endphp
                    <tr class="{{ $isMe ? 'bg-yellow-100 font-bold' : '' }}">
                        <td class="font-bold text-[#522f12]">{{ $rank }}</td>
                        <td>
                            <a href="{{ route('game.messages.create', ['to' => $p->name]) }}" class="text-[#3b200b] hover:underline font-bold" title="Message player">
                                {{ $p->name }}
                            </a>
                            @if($isMe) <span class="text-xs text-yellow-800">(You)</span> @endif
                        </td>
                        <td>
                            @if($p->tribe)
                                <a href="{{ route('game.tribe.index') }}" class="text-blue-900 font-bold hover:underline">
                                    [{{ $p->tribe->tag }}]
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="text-right font-mono font-bold text-[#5a3416]">{{ number_format($p->calculated_points) }}</td>
                        <td class="text-center font-mono">{{ $p->villages_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            {{ $players->links() }}
        </div>

    </div>

</div>
@endsection
