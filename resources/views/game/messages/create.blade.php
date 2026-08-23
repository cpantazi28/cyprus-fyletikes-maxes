@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>✏️ Compose Message</span>
            </h2>
            <a href="{{ route('game.messages.index') }}" class="tw-btn text-xs">
                &larr; Inbox
            </a>
        </div>

        <form method="POST" action="{{ route('game.messages.store') }}" class="space-y-3 max-w-xl">
            @csrf

            <div>
                <label class="block font-bold text-xs text-[#4a290e] mb-1">Recipient (Player Name):</label>
                <input type="text" name="recipient" value="{{ old('recipient', $recipientName) }}" required class="w-full bg-white border border-[#7d5128] rounded px-3 py-1.5 text-xs text-[#3b200b] focus:outline-none font-bold">
            </div>

            <div>
                <label class="block font-bold text-xs text-[#4a290e] mb-1">Subject:</label>
                <input type="text" name="subject" value="{{ old('subject', $replySubject) }}" required maxlength="100" class="w-full bg-white border border-[#7d5128] rounded px-3 py-1.5 text-xs text-[#3b200b] focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-xs text-[#4a290e] mb-1">Message Body:</label>
                <textarea name="body" rows="8" required maxlength="5000" class="w-full bg-white border border-[#7d5128] rounded p-3 text-xs text-[#3b200b] focus:outline-none">{{ old('body') }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="submit" class="tw-btn tw-btn-success text-sm py-1.5 px-6 font-bold">
                    ✉️ Send Message
                </button>
            </div>
        </form>

    </div>

</div>
@endsection
