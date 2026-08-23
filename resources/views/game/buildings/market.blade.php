@extends('layouts.game')

@section('content')
<div class="flex flex-col gap-3">

    <!-- Building Header -->
    <div class="tw-panel p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-2">
            <div>
                <h2 class="text-lg font-bold text-[#3b200b] tw-title flex items-center gap-2">
                    <span>⚖️ {{ $buildingConfig['name'] }} (Level {{ $currentLevel }})</span>
                </h2>
                <p class="text-xs text-[#6e431f]">{{ $buildingConfig['description'] }}</p>
            </div>
            <div class="text-xs text-right">
                <span class="text-[#7c4c23] font-bold">Merchants Available:</span>
                <span class="bg-[#f0e2b6] px-2 py-0.5 rounded font-mono font-bold text-[#381e08]">
                    🐫 {{ $village->available_merchants }} / {{ $village->total_merchants }}
                </span>
            </div>
        </div>

        <!-- Section 1: Send Resources -->
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2 flex items-center gap-1.5">
                <span>📦 Send Resources to Village</span>
            </h3>

            <form method="POST" action="{{ route('game.market.send') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 text-xs items-end">
                @csrf
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Target X:</label>
                    <input type="number" name="x" value="{{ request('x', $village->x) }}" required min="0" max="999" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Target Y:</label>
                    <input type="number" name="y" value="{{ request('y', $village->y) }}" required min="0" max="999" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#8a5229] mb-1">🌲 Wood:</label>
                    <input type="number" name="wood" value="0" min="0" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#c46835] mb-1">🧱 Clay:</label>
                    <input type="number" name="clay" value="0" min="0" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#54606b] mb-1">⛏️ Iron:</label>
                    <input type="number" name="iron" value="0" min="0" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div class="sm:col-span-2 md:col-span-5 flex justify-end mt-1">
                    <button type="submit" {{ $village->available_merchants < 1 ? 'disabled' : '' }} class="tw-btn tw-btn-success text-xs py-1.5 px-4 font-bold">
                        🐫 Dispatch Merchants
                    </button>
                </div>
            </form>
        </div>

        <!-- Section 2: Create Market Offer -->
        <div class="bg-[#f5ecc7] p-3 rounded border border-[#c9b183] mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#dfcf99] pb-1 mb-2 flex items-center gap-1.5">
                <span>🤝 Create Trade Offer</span>
            </h3>

            <form method="POST" action="{{ route('game.market.create-offer') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 text-xs items-end">
                @csrf
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">I Sell (Resource):</label>
                    <select name="sell_resource" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none font-semibold">
                        <option value="wood">🌲 Wood</option>
                        <option value="clay">🧱 Clay</option>
                        <option value="iron">⛏️ Iron</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Sell Amount:</label>
                    <input type="number" name="sell_amount" value="1000" min="100" step="100" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">I Buy (Resource):</label>
                    <select name="buy_resource" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none font-semibold">
                        <option value="clay">🧱 Clay</option>
                        <option value="iron">⛏️ Iron</option>
                        <option value="wood">🌲 Wood</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Buy Amount:</label>
                    <input type="number" name="buy_amount" value="1000" min="100" step="100" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div>
                    <label class="block font-bold text-[#522f12] mb-1">Max Trades:</label>
                    <input type="number" name="max_trades" value="1" min="1" max="50" class="w-full bg-white border border-[#7d5128] rounded px-2 py-1 focus:outline-none">
                </div>
                <div class="sm:col-span-2 md:col-span-5 flex justify-end mt-1">
                    <button type="submit" class="tw-btn tw-btn-success text-xs py-1.5 px-4 font-bold">
                        ⚖️ Post Trade Offer
                    </button>
                </div>
            </form>
        </div>

        <!-- Section 3: Open Market Offers from Other Players -->
        @php
            $openOffers = \App\Models\MarketOffer::where('status', 'active')
                ->where('village_id', '!=', $village->id)
                ->with(['village.user'])
                ->latest()
                ->take(20)
                ->get();
            $myOffers = $village->marketOffers;
        @endphp

        <div class="mb-4">
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                🌐 Marketplace Offers ({{ $openOffers->count() }})
            </h3>

            @if($openOffers->isEmpty())
                <p class="text-xs text-gray-600 italic py-2">No trade offers currently listed on the marketplace.</p>
            @else
                <table class="tw-table">
                    <thead>
                        <tr>
                            <th>Seller</th>
                            <th>Offered (You Receive)</th>
                            <th>Requested (You Pay)</th>
                            <th>Ratio</th>
                            <th>Available Trades</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openOffers as $offer)
                            <tr>
                                <td>
                                    <strong>{{ $offer->user->name ?? 'Player' }}</strong>
                                    <span class="text-xs text-gray-600">({{ $offer->village->coordinates }})</span>
                                </td>
                                <td class="font-bold text-[#381e08]">
                                    {{ number_format($offer->sell_amount) }} {{ ucfirst($offer->sell_resource) }}
                                </td>
                                <td class="font-bold text-[#381e08]">
                                    {{ number_format($offer->buy_amount) }} {{ ucfirst($offer->buy_resource) }}
                                </td>
                                <td class="font-mono font-bold {{ $offer->ratio <= 1.0 ? 'text-green-800' : 'text-orange-800' }}">
                                    {{ $offer->ratio }}
                                </td>
                                <td>{{ $offer->remaining_trades }}</td>
                                <td>
                                    <form method="POST" action="{{ route('game.market.accept-offer', $offer->id) }}">
                                        @csrf
                                        <button type="submit" class="tw-btn tw-btn-success text-xs py-0.5 px-2">
                                            Accept (1)
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Section 4: My Active Offers -->
        @if($myOffers->isNotEmpty())
        <div>
            <h3 class="font-bold text-sm text-[#4a290e] border-b border-[#c2a46c] pb-1 mb-2">
                📋 My Active Offers
            </h3>
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Selling</th>
                        <th>Buying</th>
                        <th>Ratio</th>
                        <th>Remaining Trades</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myOffers as $myOffer)
                        <tr>
                            <td>{{ number_format($myOffer->sell_amount) }} {{ ucfirst($myOffer->sell_resource) }}</td>
                            <td>{{ number_format($myOffer->buy_amount) }} {{ ucfirst($myOffer->buy_resource) }}</td>
                            <td>{{ $myOffer->ratio }}</td>
                            <td>{{ $myOffer->remaining_trades }} / {{ $myOffer->max_trades }}</td>
                            <td>
                                <form method="POST" action="{{ route('game.market.cancel-offer', $myOffer->id) }}">
                                    @csrf
                                    <button type="submit" class="tw-btn tw-btn-danger text-xs py-0.5 px-2">
                                        Cancel Offer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

</div>
@endsection
