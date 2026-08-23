@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>🛡️ [{{ $tribe->tag }}] {{ $tribe->name }}</span>
                </h2>
                <span class="text-xs text-[#7c4c23]">Rank: <strong>#{{ $tribe->rank }}</strong> &bull; Total Points: <strong>{{ number_format($tribe->points) }}</strong> &bull; Members: <strong>{{ $tribe->member_count }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('game.tribe.forum') }}" class="tw-btn text-xs font-bold">
                    💬 Tribe Forum
                </a>
                <a href="{{ route('game.tribe.members') }}" class="tw-btn text-xs font-bold">
                    👥 Members ({{ $tribe->member_count }})
                </a>
                <a href="{{ route('game.tribe.diplomacy') }}" class="tw-btn text-xs font-bold">
                    🤝 Diplomacy
                </a>
                <form method="POST" action="{{ route('game.tribe.leave') }}" onsubmit="return confirm('Are you sure you want to leave the tribe?')">
                    @csrf
                    <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5 px-2">Leave Tribe</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            
            <!-- Tribe Description / Profile -->
            <div class="md:col-span-2 bg-[#f5ecc7] p-3 rounded border border-[#c9b183]">
                <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2">
                    📜 Tribe Profile
                </h3>
                <div class="whitespace-pre-wrap text-[#2b1704] leading-relaxed min-h-24">
                    {{ $tribe->description ?: 'No tribe description set.' }}
                </div>
            </div>

            <!-- Tribe Leadership & Quick Info -->
            <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] space-y-3">
                <div>
                    <h4 class="font-bold text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-1">👑 Founder</h4>
                    <p class="font-bold text-[#3b200b]">{{ $tribe->founder->name ?? 'None' }}</p>
                </div>

                <div>
                    <h4 class="font-bold text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-1">Your Role</h4>
                    <p class="font-bold capitalize {{ $membership->isLeader() ? 'text-green-800' : 'text-[#3b200b]' }}">{{ $membership->role }}</p>
                </div>

                <div>
                    <h4 class="font-bold text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-1">Diplomacy Overview</h4>
                    <p>Allies: <strong>{{ $tribe->allies->count() }}</strong></p>
                    <p>NAPs: <strong>{{ $tribe->naps->count() }}</strong></p>
                    <p>Enemies: <strong>{{ $tribe->enemies->count() }}</strong></p>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
