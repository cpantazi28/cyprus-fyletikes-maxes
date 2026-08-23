@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-3">
            <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                <span>📜 Reports</span>
            </h2>
            <form method="POST" action="{{ route('game.reports.mark-read') }}">
                @csrf
                <button type="submit" class="tw-btn text-xs py-0.5 px-2">Mark All as Read</button>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-1 mb-3 text-xs">
            <a href="{{ route('game.reports.index') }}" class="tw-btn py-1 {{ empty($currentFilter) ? 'tw-btn-success' : '' }}">All</a>
            <a href="{{ route('game.reports.filter', 'attack') }}" class="tw-btn py-1 {{ ($currentFilter ?? '') === 'attack' ? 'tw-btn-success' : '' }}">Attacks</a>
            <a href="{{ route('game.reports.filter', 'defense') }}" class="tw-btn py-1 {{ ($currentFilter ?? '') === 'defense' ? 'tw-btn-success' : '' }}">Defenses</a>
            <a href="{{ route('game.reports.filter', 'support') }}" class="tw-btn py-1 {{ ($currentFilter ?? '') === 'support' ? 'tw-btn-success' : '' }}">Support</a>
        </div>

        <!-- Reports List Table -->
        @if($reports->isEmpty())
            <p class="text-xs text-gray-600 italic py-4 text-center">No reports found.</p>
        @else
            <table class="tw-table">
                <thead>
                    <tr>
                        <th class="w-8"></th>
                        <th>Subject</th>
                        <th class="w-36">Date</th>
                        <th class="w-16">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $rep)
                        <tr class="{{ !$rep->is_read ? 'font-bold bg-[#fcf2cf]' : '' }}">
                            <td class="text-center">
                                @if(!$rep->is_read)
                                    <span class="inline-block w-2.5 h-2.5 bg-blue-600 rounded-full" title="Unread"></span>
                                @else
                                    <span class="text-xs text-gray-400">📜</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('game.reports.show', $rep->id) }}" class="text-[#4a290e] hover:underline">
                                    {{ $rep->title }}
                                </a>
                            </td>
                            <td class="text-xs font-mono text-gray-700">
                                {{ $rep->created_at->format('H:i:s d/m/Y') }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('game.reports.destroy', $rep->id) }}">
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
                {{ $reports->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
