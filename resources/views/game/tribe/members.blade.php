@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>👥 [{{ $tribe->tag }}] Member Roster</span>
                </h2>
            </div>
            <a href="{{ route('game.tribe.index') }}" class="tw-btn text-xs">
                &larr; Tribe Overview
            </a>
        </div>

        <table class="tw-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Player</th>
                    <th>Role</th>
                    <th>Points</th>
                    <th>Villages</th>
                    @if($isLeader)
                        <th>Manage Role</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($members as $m)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ route('game.messages.create', ['to' => $m->user->name]) }}" class="font-bold text-[#3b200b] hover:underline" title="Send message">
                                {{ $m->user->name }}
                            </a>
                        </td>
                        <td class="capitalize font-semibold text-[#5a3416]">{{ $m->role }}</td>
                        <td class="font-mono font-bold">{{ number_format($m->user->total_points) }}</td>
                        <td>{{ $m->user->villages->count() }}</td>
                        @if($isLeader)
                            <td>
                                @if($m->role !== 'founder')
                                    <form method="POST" action="{{ route('game.tribe.update-role', $m->id) }}" class="flex items-center gap-1">
                                        @csrf
                                        <select name="role" class="bg-white border border-[#7d5128] rounded px-1 py-0.5 text-xs">
                                            <option value="baron" {{ $m->role === 'baron' ? 'selected' : '' }}>Baron</option>
                                            <option value="diplomat" {{ $m->role === 'diplomat' ? 'selected' : '' }}>Diplomat</option>
                                            <option value="recruiter" {{ $m->role === 'recruiter' ? 'selected' : '' }}>Recruiter</option>
                                            <option value="member" {{ $m->role === 'member' ? 'selected' : '' }}>Member</option>
                                        </select>
                                        <button type="submit" class="tw-btn text-[10px] py-0.5 px-1.5">Save</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500 italic">Founder</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</div>
@endsection
