<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Tribal Wars') }} - {{ $currentVillage->name ?? 'Tribal Wars' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cinzel:600,700,900|medievalsharp:400|source-sans-3:400,600,700" rel="stylesheet" />

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --tw-parchment: #f4e8c1;
            --tw-parchment-dark: #dfcf99;
            --tw-wood-dark: #3b200b;
            --tw-wood-medium: #5a3416;
            --tw-wood-light: #7c4c23;
            --tw-gold: #c59b27;
            --tw-gold-light: #e6be44;
            --tw-border: #7d5128;
            --tw-table-header: #c1a265;
            --tw-table-row-alt: #f0e2b6;
        }

        body {
            background-color: #241407;
            background-image: radial-gradient(circle at 50% 50%, rgba(59, 32, 11, 0.8), rgba(20, 10, 3, 0.95)), url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23361c07' fill-opacity='0.25' fill-rule='evenodd'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E");
            font-family: 'Source Sans 3', sans-serif;
            color: #2b1704;
        }

        .tw-title {
            font-family: 'Cinzel', serif;
        }

        .tw-panel {
            background: #fdf5e6;
            border: 2px solid #6e431f;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5), inset 0 0 10px rgba(110, 67, 31, 0.15);
            border-radius: 4px;
        }

        .tw-panel-header {
            background: linear-gradient(180deg, #7c4c23 0%, #4a290e 100%);
            color: #f7e7be;
            border-bottom: 2px solid #381e08;
            font-family: 'Cinzel', serif;
            text-shadow: 1px 1px 2px #000;
        }

        .tw-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #7d5128;
            font-size: 0.925rem;
        }

        .tw-table th {
            background: linear-gradient(180deg, #d8bc7d 0%, #b89752 100%);
            color: #2e1805;
            padding: 6px 8px;
            font-weight: 700;
            border: 1px solid #7d5128;
            text-align: left;
        }

        .tw-table td {
            padding: 6px 8px;
            border: 1px solid #c9b183;
        }

        .tw-table tr:nth-child(even) {
            background-color: #f5ecc7;
        }

        .tw-table tr:nth-child(odd) {
            background-color: #fdf6dc;
        }

        .tw-btn {
            background: linear-gradient(180deg, #855325 0%, #522f12 100%);
            color: #fff2cc;
            border: 1px solid #361c07;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: 600;
            cursor: pointer;
            text-shadow: 1px 1px 1px #000;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .tw-btn:hover:not(:disabled) {
            background: linear-gradient(180deg, #a36832 0%, #693d18 100%);
            color: #ffffff;
            box-shadow: 0 0 6px rgba(230, 190, 68, 0.5);
        }

        .tw-btn:disabled {
            background: #8c8275;
            color: #cfc8be;
            cursor: not-allowed;
            border-color: #635b52;
            opacity: 0.65;
        }

        .tw-btn-success {
            background: linear-gradient(180deg, #447a28 0%, #294d16 100%);
            color: #e5ffd1;
            border-color: #1a330e;
        }
        .tw-btn-success:hover:not(:disabled) {
            background: linear-gradient(180deg, #579934 0%, #34611d 100%);
            color: #ffffff;
        }

        .tw-btn-danger {
            background: linear-gradient(180deg, #992828 0%, #611818 100%);
            color: #ffe0e0;
            border-color: #3b0e0e;
        }
        .tw-btn-danger:hover:not(:disabled) {
            background: linear-gradient(180deg, #ba3434 0%, #7d1f1f 100%);
            color: #ffffff;
        }

        .tw-nav-link {
            padding: 6px 10px;
            color: #f7e7be;
            font-weight: 600;
            border-radius: 3px;
            text-shadow: 1px 1px 2px #000;
            transition: background 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .tw-nav-link:hover {
            background: rgba(230, 190, 68, 0.25);
            color: #ffffff;
        }
        .tw-nav-link.active {
            background: linear-gradient(180deg, #965f2e 0%, #633b16 100%);
            border: 1px solid #c59b27;
            color: #fff2cc;
        }

        /* Pulse animation for incoming attacks */
        @keyframes pulse-red {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        .pulse-attack {
            animation: pulse-red 1s infinite;
        }
    </style>
</head>
<body class="min-h-screen antialiased flex flex-col items-center">

    <!-- Top Server Banner & Authenticated Info -->
    <div class="w-full bg-[#1b0d04] border-b border-[#4d290e] text-xs text-[#d1b988] py-1 px-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span class="font-bold text-[#e6be44] tw-title">TRIBAL WARS</span>
                <span>World 1 (Speed: {{ config('game.world.speed') }}x)</span>
                <span>Server Time: <strong id="server-clock">{{ now()->format('H:i:s') }}</strong></span>
            </div>
            <div class="flex items-center space-x-4">
                <span>Player: <strong class="text-[#f5e2b6]">{{ Auth::user()->name }}</strong> ({{ Auth::user()->total_points }} pts)</span>
                @if(Auth::user()->tribe)
                    <span>Tribe: <a href="{{ route('game.tribe.index') }}" class="text-[#e6be44] hover:underline font-bold">[{{ Auth::user()->tribe->tag }}]</a></span>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-[#e6be44] hover:underline cursor-pointer">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Game Container -->
    <div class="w-full max-w-6xl my-3 px-2 sm:px-4 flex flex-col gap-3">

        <!-- Village Header Status Bar -->
        @if(isset($currentVillage))
        <div class="tw-panel p-2 shadow-lg">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#c2a46c] pb-2 mb-2">
                
                <!-- Village Switcher & Coordinates -->
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wider font-bold text-[#693d18]">Village:</span>
                    <form method="GET" action="{{ url()->current() }}" class="inline">
                        <select name="village" onchange="this.form.submit()" class="bg-[#fcf5e2] border border-[#7d5128] font-bold text-[#381e08] rounded px-2 py-1 text-sm focus:outline-none">
                            @foreach($userVillages as $uv)
                                <option value="{{ $uv->id }}" {{ $uv->id === $currentVillage->id ? 'selected' : '' }}>
                                    {{ $uv->name }} ({{ $uv->coordinates }}) - {{ $uv->continent }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <span class="text-xs bg-[#7c4c23] text-[#fff2cc] px-2 py-0.5 rounded font-mono font-bold">{{ $currentVillage->coordinates }} - {{ $currentVillage->continent }}</span>
                    <span class="text-xs text-[#522f12]">({{ $currentVillage->points }} pts)</span>
                </div>

                <!-- Live Resources Tickers -->
                <div class="flex items-center gap-4 text-sm font-bold">
                    
                    <!-- Wood -->
                    <div class="flex items-center gap-1.5" title="Wood: +{{ $currentVillage->wood_production_per_hour }}/hr">
                        <span class="inline-block w-4 h-4 rounded bg-[#8a5229] border border-[#3d1f08] text-center text-[10px] text-white font-bold leading-3.5">W</span>
                        <span id="res-wood" class="text-[#3b200b]">{{ number_format(floor($currentVillage->wood)) }}</span>
                        <span class="text-xs text-[#7d5c36]">/{{ number_format($currentVillage->storage_capacity) }}</span>
                    </div>

                    <!-- Clay -->
                    <div class="flex items-center gap-1.5" title="Clay: +{{ $currentVillage->clay_production_per_hour }}/hr">
                        <span class="inline-block w-4 h-4 rounded bg-[#c46835] border border-[#3d1f08] text-center text-[10px] text-white font-bold leading-3.5">C</span>
                        <span id="res-clay" class="text-[#3b200b]">{{ number_format(floor($currentVillage->clay)) }}</span>
                        <span class="text-xs text-[#7d5c36]">/{{ number_format($currentVillage->storage_capacity) }}</span>
                    </div>

                    <!-- Iron -->
                    <div class="flex items-center gap-1.5" title="Iron: +{{ $currentVillage->iron_production_per_hour }}/hr">
                        <span class="inline-block w-4 h-4 rounded bg-[#707c87] border border-[#3d1f08] text-center text-[10px] text-white font-bold leading-3.5">I</span>
                        <span id="res-iron" class="text-[#3b200b]">{{ number_format(floor($currentVillage->iron)) }}</span>
                        <span class="text-xs text-[#7d5c36]">/{{ number_format($currentVillage->storage_capacity) }}</span>
                    </div>

                    <!-- Farm Population -->
                    <div class="flex items-center gap-1.5" title="Population: {{ $currentVillage->used_population }} used of {{ $currentVillage->max_population }}">
                        <span class="inline-block w-4 h-4 rounded bg-[#b89752] border border-[#3d1f08] text-center text-[10px] text-white font-bold leading-3.5">P</span>
                        <span class="{{ $currentVillage->free_population < 50 ? 'text-red-700 font-extrabold' : 'text-[#3b200b]' }}">
                            {{ number_format($currentVillage->used_population) }}/{{ number_format($currentVillage->max_population) }}
                        </span>
                    </div>

                </div>

                <!-- Commands Status Badges -->
                <div class="flex items-center gap-2">
                    @php
                        $incomingAttacksCount = $currentVillage->incomingAttacks()->count();
                        $outgoingMovementsCount = $currentVillage->outgoingMovements()->count();
                    @endphp

                    @if($incomingAttacksCount > 0)
                        <a href="{{ route('game.place') }}" class="flex items-center gap-1 bg-red-700 text-white px-2 py-0.5 rounded text-xs font-bold pulse-attack border border-red-950" title="Incoming attacks!">
                            ⚔️ <span>{{ $incomingAttacksCount }} incoming</span>
                        </a>
                    @endif

                    @if($outgoingMovementsCount > 0)
                        <a href="{{ route('game.place') }}" class="flex items-center gap-1 bg-[#476e2c] text-[#e8f7dc] px-2 py-0.5 rounded text-xs font-bold border border-[#1f380f]" title="Outgoing troop commands">
                            🛡️ <span>{{ $outgoingMovementsCount }} commands</span>
                        </a>
                    @endif
                </div>

            </div>

            <!-- Main Navigation Menu -->
            <nav class="flex flex-wrap items-center gap-1 bg-[#4a290e] p-1 rounded border border-[#2b1704]">
                <a href="{{ route('game.overview') }}" class="tw-nav-link {{ request()->routeIs('game.overview') ? 'active' : '' }}">
                    🏰 Overview
                </a>
                <a href="{{ route('game.building.show', 'main') }}" class="tw-nav-link {{ request()->is('game/building/main*') ? 'active' : '' }}">
                    🏛️ Headquarters
                </a>
                @if($currentVillage->barracks > 0)
                <a href="{{ route('game.building.show', 'barracks') }}" class="tw-nav-link {{ request()->is('game/building/barracks*') ? 'active' : '' }}">
                    ⚔️ Barracks
                </a>
                @endif
                @if($currentVillage->stable > 0)
                <a href="{{ route('game.building.show', 'stable') }}" class="tw-nav-link {{ request()->is('game/building/stable*') ? 'active' : '' }}">
                    🐎 Stable
                </a>
                @endif
                @if($currentVillage->garage > 0)
                <a href="{{ route('game.building.show', 'garage') }}" class="tw-nav-link {{ request()->is('game/building/garage*') ? 'active' : '' }}">
                    🪵 Workshop
                </a>
                @endif
                @if($currentVillage->snob > 0)
                <a href="{{ route('game.building.show', 'snob') }}" class="tw-nav-link {{ request()->is('game/building/snob*') ? 'active' : '' }}">
                    👑 Academy
                </a>
                @endif
                @if($currentVillage->smith > 0)
                <a href="{{ route('game.building.show', 'smith') }}" class="tw-nav-link {{ request()->is('game/building/smith*') ? 'active' : '' }}">
                    ⚒️ Smithy
                </a>
                @endif
                <a href="{{ route('game.place') }}" class="tw-nav-link {{ request()->routeIs('game.place*') ? 'active' : '' }}">
                    🚩 Rally Point
                </a>
                @if($currentVillage->market > 0)
                <a href="{{ route('game.building.show', 'market') }}" class="tw-nav-link {{ request()->is('game/building/market*') ? 'active' : '' }}">
                    ⚖️ Market
                </a>
                @endif
                <a href="{{ route('game.map') }}" class="tw-nav-link {{ request()->routeIs('game.map*') ? 'active' : '' }}">
                    🗺️ Map
                </a>
                <a href="{{ route('game.tribe.index') }}" class="tw-nav-link {{ request()->routeIs('game.tribe*') ? 'active' : '' }}">
                    🛡️ Tribe
                </a>
                <a href="{{ route('game.reports.index') }}" class="tw-nav-link {{ request()->routeIs('game.reports*') ? 'active' : '' }}">
                    📜 Reports
                    @if(($unreadReportsCount ?? 0) > 0)
                        <span class="bg-red-600 text-white text-[10px] px-1.5 py-0.2 rounded-full font-bold">({{ $unreadReportsCount }})</span>
                    @endif
                </a>
                <a href="{{ route('game.messages.index') }}" class="tw-nav-link {{ request()->routeIs('game.messages*') ? 'active' : '' }}">
                    ✉️ Mail
                    @if(($unreadMessagesCount ?? 0) > 0)
                        <span class="bg-yellow-500 text-black text-[10px] px-1.5 py-0.2 rounded-full font-bold">({{ $unreadMessagesCount }})</span>
                    @endif
                </a>
                <a href="{{ route('game.ranking.players') }}" class="tw-nav-link {{ request()->routeIs('game.ranking*') ? 'active' : '' }}">
                    🏆 Ranking
                </a>
            </nav>
        </div>
        @endif

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="bg-[#d4edda] border-2 border-[#28a745] text-[#155724] px-4 py-2 rounded text-sm font-semibold shadow flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-lg leading-none cursor-pointer">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-[#f8d7da] border-2 border-[#dc3545] text-[#721c24] px-4 py-2 rounded text-sm font-semibold shadow flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" class="text-lg leading-none cursor-pointer">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-[#f8d7da] border-2 border-[#dc3545] text-[#721c24] px-4 py-2 rounded text-sm shadow">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Main View Content Area -->
        <main class="w-full">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="text-center text-xs text-[#9e7d56] py-4 border-t border-[#4a290e] mt-4">
            <p>Tribal Wars (Classic TW1 Engine) &bull; Game Speed: {{ config('game.world.speed') }}x &bull; Unit Speed: {{ config('game.world.unit_speed') }}x</p>
        </footer>

    </div>

    <!-- Live Resource & Countdown JavaScript Ticker -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if(isset($currentVillage))
                let wood = {{ (float) $currentVillage->wood }};
                let clay = {{ (float) $currentVillage->clay }};
                let iron = {{ (float) $currentVillage->iron }};

                const maxStorage = {{ (int) $currentVillage->storage_capacity }};
                const woodPerSec = {{ $currentVillage->wood_production_per_hour }} / 3600;
                const clayPerSec = {{ $currentVillage->clay_production_per_hour }} / 3600;
                const ironPerSec = {{ $currentVillage->iron_production_per_hour }} / 3600;

                const woodEl = document.getElementById('res-wood');
                const clayEl = document.getElementById('res-clay');
                const ironEl = document.getElementById('res-iron');

                setInterval(() => {
                    wood = Math.min(maxStorage, wood + woodPerSec);
                    clay = Math.min(maxStorage, clay + clayPerSec);
                    iron = Math.min(maxStorage, iron + ironPerSec);

                    if (woodEl) woodEl.innerText = Math.floor(wood).toLocaleString();
                    if (clayEl) clayEl.innerText = Math.floor(clay).toLocaleString();
                    if (ironEl) ironEl.innerText = Math.floor(iron).toLocaleString();
                }, 1000);
            @endif

            // Update countdown timers dynamically
            function updateTimers() {
                const timerElements = document.querySelectorAll('[data-countdown]');
                timerElements.forEach(el => {
                    let seconds = parseInt(el.getAttribute('data-countdown'), 10);
                    if (seconds > 0) {
                        seconds--;
                        el.setAttribute('data-countdown', seconds);
                        
                        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
                        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
                        const s = String(seconds % 60).padStart(2, '0');
                        el.innerText = `${h}:${m}:${s}`;
                    } else if (seconds === 0) {
                        el.innerText = '00:00:00';
                        // Auto-refresh when an active queue finishes
                        if (el.getAttribute('data-autorefresh') === 'true') {
                            el.removeAttribute('data-autorefresh');
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    }
                });
            }
            setInterval(updateTimers, 1000);

            // Server clock
            const clockEl = document.getElementById('server-clock');
            if (clockEl) {
                setInterval(() => {
                    const now = new Date();
                    clockEl.innerText = now.toTimeString().split(' ')[0];
                }, 1000);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
