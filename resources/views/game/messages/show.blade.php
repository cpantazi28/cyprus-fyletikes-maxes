@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <div>
                <h2 class="text-base font-bold text-[#3b200b] tw-title">{{ $message->subject }}</h2>
                <div class="text-xs text-[#7c4c23] space-x-2">
                    <span>From: <strong>{{ $message->sender->name ?? 'Unknown' }}</strong></span>
                    <span>To: <strong>{{ $message->recipient->name ?? 'Unknown' }}</strong></span>
                    <span class="font-mono">({{ $message->created_at->format('H:i:s d/m/Y') }})</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('game.messages.index') }}" class="tw-btn text-xs">
                    &larr; Inbox
                </a>
                @if($isRecipient)
                    <a href="{{ route('game.messages.create', ['to' => $message->sender->name ?? '', 'subject' => 'Re: ' . $message->subject]) }}" class="tw-btn tw-btn-success text-xs py-1 px-3 font-bold">
                        ↩️ Reply
                    </a>
                @endif
                <form method="POST" action="{{ route('game.messages.destroy', $message->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5">Delete</button>
                </form>
            </div>
        </div>

        <div class="bg-[#fdfaf2] p-4 rounded border border-[#dfcf99] text-xs text-[#2b1704] leading-relaxed whitespace-pre-wrap min-h-36">
{{ $message->body }}
        </div>

    </div>

</div>
@endsection
