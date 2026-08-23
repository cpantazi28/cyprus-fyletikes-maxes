@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>💬 [{{ $tribe->tag }}] Internal Forum</span>
            </h2>
            <a href="{{ route('game.tribe.index') }}" class="tw-btn text-xs">
                &larr; Tribe Overview
            </a>
        </div>

        <!-- Create Thread Form -->
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-xs text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2">
                📝 Post New Topic
            </h3>

            <form method="POST" action="{{ route('game.tribe.store-thread') }}" class="space-y-2 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Topic Title:</label>
                    <input type="text" name="title" required maxlength="150" placeholder="e.g. Defense Coordination in K55" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 font-bold focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Message:</label>
                    <textarea name="body" rows="3" required maxlength="10000" class="w-full bg-white border border-[#7d5128] rounded p-2 focus:outline-none"></textarea>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-1.5 text-xs">
                        <input type="checkbox" name="is_sticky" value="1">
                        <span class="font-bold text-[#522f12]">Pin Topic (Sticky)</span>
                    </label>
                    <button type="submit" class="tw-btn tw-btn-success py-1 px-4 font-bold">
                        Post Topic
                    </button>
                </div>
            </form>
        </div>

        <!-- Topics Table -->
        <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
            📋 Topics
        </h3>

        @if($threads->isEmpty())
            <p class="text-xs text-gray-600 italic py-4 text-center">No discussion topics posted yet.</p>
        @else
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th class="w-28 text-center">Author</th>
                        <th class="w-16 text-center">Replies</th>
                        <th class="w-16 text-center">Views</th>
                        <th class="w-36 text-center">Last Post</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($threads as $th)
                        <tr>
                            <td>
                                @if($th->is_sticky)
                                    <span class="text-xs text-yellow-800 font-bold mr-1">📌 [Sticky]</span>
                                @endif
                                <a href="{{ route('game.tribe.thread', $th->id) }}" class="font-bold text-[#4a290e] hover:underline">
                                    {{ $th->title }}
                                </a>
                            </td>
                            <td class="text-center text-xs font-semibold">{{ $th->user->name ?? 'Player' }}</td>
                            <td class="text-center font-mono text-xs">{{ $th->posts_count }}</td>
                            <td class="text-center font-mono text-xs">{{ $th->views }}</td>
                            <td class="text-center text-xs font-mono text-gray-700">
                                {{ $th->updated_at->format('H:i d/m') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $threads->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
