@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-base font-bold text-[#3b200b] tw-title flex items-center gap-1.5">
                    @if($thread->is_sticky)
                        <span class="text-yellow-800">📌</span>
                    @endif
                    <span>{{ $thread->title }}</span>
                </h2>
                <span class="text-xs text-[#7c4c23]">Posted by <strong>{{ $thread->user->name ?? 'Player' }}</strong> on {{ $thread->created_at->format('H:i:s d/m/Y') }}</span>
            </div>
            <a href="{{ route('game.tribe.forum') }}" class="tw-btn text-xs">
                &larr; Forum Topics
            </a>
        </div>

        <!-- Posts List -->
        <div class="space-y-3 mb-4">
            @foreach($thread->posts as $post)
                <div class="bg-[#fdfaf2] rounded border border-[#dfcf99] overflow-hidden text-xs">
                    <div class="bg-[#f0e2b6] px-3 py-1.5 border-b border-[#dfcf99] flex justify-between items-center">
                        <span class="font-bold text-[#4a290e]">{{ $post->user->name ?? 'Player' }}</span>
                        <span class="text-gray-600 font-mono">{{ $post->created_at->format('H:i:s d/m/Y') }}</span>
                    </div>
                    <div class="p-3 text-[#2b1704] whitespace-pre-wrap leading-relaxed">
{{ $post->body }}
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Reply Form -->
        @if(!$thread->is_locked)
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183]">
            <h4 class="font-bold text-xs text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2">
                💬 Post Reply
            </h4>
            <form method="POST" action="{{ route('game.tribe.store-post', $thread->id) }}" class="space-y-2">
                @csrf
                <textarea name="body" rows="4" required maxlength="10000" placeholder="Write your reply..." class="w-full bg-white border border-[#7d5128] rounded p-2 text-xs text-[#3b200b] focus:outline-none"></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="tw-btn tw-btn-success text-xs py-1.5 px-4 font-bold">
                        Submit Reply
                    </button>
                </div>
            </form>
        </div>
        @endif

    </div>

</div>
@endsection
