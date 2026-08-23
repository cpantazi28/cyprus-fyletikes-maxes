@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>✉️ Sent Messages</span>
            </h2>
            <a href="{{ route('game.messages.create') }}" class="tw-btn tw-btn-success text-xs py-1 px-3 font-bold">
                ✏️ Compose Message
            </a>
        </div>

        <div class="flex items-center gap-1 mb-3 text-xs">
            <a href="{{ route('game.messages.index') }}" class="tw-btn py-1">Inbox</a>
            <a href="{{ route('game.messages.sent') }}" class="tw-btn py-1 tw-btn-success">Sent Messages</a>
            <a href="{{ route('game.messages.create') }}" class="tw-btn py-1">Write New</a>
        </div>

        @if($messages->isEmpty())
            <p class="text-xs text-gray-600 italic py-4 text-center">No sent messages.</p>
        @else
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th class="w-36">Recipient</th>
                        <th class="w-36">Date</th>
                        <th class="w-16">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                        <tr>
                            <td>
                                <a href="{{ route('game.messages.show', $msg->id) }}" class="text-[#4a290e] hover:underline font-semibold">
                                    {{ $msg->subject }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $msg->recipient->name ?? 'Player' }}</strong>
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
