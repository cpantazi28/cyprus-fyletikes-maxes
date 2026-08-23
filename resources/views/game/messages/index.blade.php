@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>✉️ In-Game Mail</span>
            </h2>
            <a href="{{ route('game.messages.create') }}" class="tw-btn tw-btn-success text-xs py-1 px-3 font-bold">
                ✏️ Compose Message
            </a>
        </div>

        <!-- Mail Tabs -->
        <div class="flex items-center gap-1 mb-3 text-xs">
            <a href="{{ route('game.messages.index') }}" class="tw-btn py-1 tw-btn-success">Inbox</a>
            <a href="{{ route('game.messages.sent') }}" class="tw-btn py-1">Sent Messages</a>
            <a href="{{ route('game.messages.create') }}" class="tw-btn py-1">Write New</a>
        </div>

        <!-- Inbox Table -->
        @if($messages->isEmpty())
            <p class="text-xs text-gray-600 italic py-4 text-center">Your inbox is empty.</p>
        @else
            <table class="tw-table">
                <thead>
                    <tr>
                        <th class="w-8"></th>
                        <th>Subject</th>
                        <th class="w-36">Sender</th>
                        <th class="w-36">Date</th>
                        <th class="w-16">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                        <tr class="{{ !$msg->is_read ? 'font-bold bg-[#fcf2cf]' : '' }}">
                            <td class="text-center">
                                @if(!$msg->is_read)
                                    <span class="inline-block w-2.5 h-2.5 bg-yellow-500 rounded-full" title="Unread"></span>
                                @else
                                    <span class="text-xs text-gray-400">✉️</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('game.messages.show', $msg->id) }}" class="text-[#4a290e] hover:underline">
                                    {{ $msg->subject }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $msg->sender->name ?? 'Deleted' }}</strong>
                            </td>
                            <td class="text-xs font-mono text-gray-700">
                                {{ $msg->created_at->format('H:i:s d/m/Y') }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('game.messages.destroy', $msg->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-700 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $messages->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
