<?php

use App\Http\Controllers\Game\BuildingController;
use App\Http\Controllers\Game\MapController;
use App\Http\Controllers\Game\MarketController;
use App\Http\Controllers\Game\MessageController;
use App\Http\Controllers\Game\RallyPointController;
use App\Http\Controllers\Game\RankingController;
use App\Http\Controllers\Game\ReportController;
use App\Http\Controllers\Game\TribeController;
use App\Http\Controllers\Game\VillageController;
use App\Http\Middleware\EnsurePlayerVillage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('game.overview');
    }

    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('game.overview');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', EnsurePlayerVillage::class])->prefix('game')->name('game.')->group(function () {
    // Village Overview
    Route::get('/', [VillageController::class, 'overview'])->name('overview');
    Route::post('/village/rename', [VillageController::class, 'rename'])->name('village.rename');

    // Building Screens & Queues
    Route::get('/building/{building}', [BuildingController::class, 'show'])->name('building.show');
    Route::post('/building/{building}/upgrade', [BuildingController::class, 'upgrade'])->name('building.upgrade');
    Route::post('/building/cancel-upgrade/{id}', [BuildingController::class, 'cancelUpgrade'])->name('building.cancel-upgrade');
    Route::post('/building/{building}/recruit', [BuildingController::class, 'recruit'])->name('building.recruit');
    Route::post('/building/cancel-recruit/{id}', [BuildingController::class, 'cancelRecruitment'])->name('building.cancel-recruit');
    Route::post('/building/snob/mint-coin', [BuildingController::class, 'mintCoin'])->name('building.mint-coin');
    Route::post('/building/{building}/demolish', [BuildingController::class, 'demolish'])->name('building.demolish');

    // Rally Point & Commands
    Route::get('/place', [RallyPointController::class, 'index'])->name('place');
    Route::post('/place/confirm', [RallyPointController::class, 'confirm'])->name('place.confirm');
    Route::post('/place/send', [RallyPointController::class, 'send'])->name('place.send');
    Route::post('/place/cancel/{id}', [RallyPointController::class, 'cancel'])->name('place.cancel');
    Route::post('/place/send-back/{id}', [RallyPointController::class, 'sendBackSupport'])->name('place.send-back');
    Route::match(['get', 'post'], '/simulator', [RallyPointController::class, 'simulator'])->name('simulator');

    // Interactive Map
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/map/sector', [MapController::class, 'sector'])->name('map.sector');

    // Marketplace
    Route::post('/market/send', [MarketController::class, 'sendResources'])->name('market.send');
    Route::post('/market/offer', [MarketController::class, 'createOffer'])->name('market.create-offer');
    Route::post('/market/accept/{id}', [MarketController::class, 'acceptOffer'])->name('market.accept-offer');
    Route::post('/market/cancel-offer/{id}', [MarketController::class, 'cancelOffer'])->name('market.cancel-offer');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/category/{filter}', [ReportController::class, 'index'])->name('reports.filter');
    Route::get('/reports/view/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::delete('/reports/{id}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::post('/reports/mark-read', [ReportController::class, 'markAllRead'])->name('reports.mark-read');

    // In-game Messages (Mail)
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{id}', [MessageController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // Tribes
    Route::get('/tribe', [TribeController::class, 'index'])->name('tribe.index');
    Route::post('/tribe', [TribeController::class, 'store'])->name('tribe.store');
    Route::get('/tribe/members', [TribeController::class, 'members'])->name('tribe.members');
    Route::post('/tribe/members/{id}/role', [TribeController::class, 'updateRole'])->name('tribe.update-role');
    Route::get('/tribe/diplomacy', [TribeController::class, 'diplomacy'])->name('tribe.diplomacy');
    Route::post('/tribe/diplomacy', [TribeController::class, 'storeDiplomacy'])->name('tribe.store-diplomacy');
    Route::get('/tribe/forum', [TribeController::class, 'forum'])->name('tribe.forum');
    Route::get('/tribe/forum/thread/{id}', [TribeController::class, 'forumThread'])->name('tribe.thread');
    Route::post('/tribe/forum/thread', [TribeController::class, 'storeThread'])->name('tribe.store-thread');
    Route::post('/tribe/forum/thread/{id}/post', [TribeController::class, 'storePost'])->name('tribe.store-post');
    Route::post('/tribe/leave', [TribeController::class, 'leave'])->name('tribe.leave');

    // Rankings
    Route::get('/ranking/players', [RankingController::class, 'players'])->name('ranking.players');
    Route::get('/ranking/tribes', [RankingController::class, 'tribes'])->name('ranking.tribes');
});

require __DIR__.'/settings.php';
