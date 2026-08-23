<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\MarketOffer;
use App\Models\MarketTransport;
use App\Models\Village;
use App\Services\GameEngine;
use App\Services\ResourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function __construct(
        protected ResourceService $resourceService,
        protected GameEngine $gameEngine
    ) {}

    /**
     * Send resources via merchants.
     */
    public function sendResources(Request $request): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $x = (int) $request->input('x');
        $y = (int) $request->input('y');
        $wood = max(0, (int) $request->input('wood', 0));
        $clay = max(0, (int) $request->input('clay', 0));
        $iron = max(0, (int) $request->input('iron', 0));

        $totalRes = $wood + $clay + $iron;
        if ($totalRes <= 0) {
            return back()->with('error', 'Please specify resources to transport.');
        }

        $targetVillage = Village::where('x', $x)->where('y', $y)->first();
        if (! $targetVillage) {
            return back()->with('error', "Village at {$x}|{$y} not found.");
        }

        if ($targetVillage->id === $village->id) {
            return back()->with('error', 'Cannot transport resources to the same village.');
        }

        $merchantCapacity = config('game.buildings.market.merchant_capacity', 1000);
        $merchantsNeeded = (int) ceil($totalRes / $merchantCapacity);

        if ($village->available_merchants < $merchantsNeeded) {
            return back()->with('error', "Not enough merchants available ({$village->available_merchants} available, {$merchantsNeeded} needed).");
        }

        $this->resourceService->accrueResources($village);
        if (! $this->resourceService->canAfford($village, $wood, $clay, $iron)) {
            return back()->with('error', 'Not enough resources in village.');
        }

        $this->resourceService->deductResources($village, $wood, $clay, $iron);

        $distance = $village->distanceTo($targetVillage);
        $speedMinutesPerField = config('game.buildings.market.merchant_speed', 6);
        $worldSpeed = config('game.world.speed', 1.0);
        $durationSeconds = max(10, (int) round(($distance * $speedMinutesPerField * 60) / $worldSpeed));

        MarketTransport::create([
            'source_village_id' => $village->id,
            'target_village_id' => $targetVillage->id,
            'wood' => $wood,
            'clay' => $clay,
            'iron' => $iron,
            'merchants' => $merchantsNeeded,
            'departs_at' => now(),
            'arrives_at' => now()->addSeconds($durationSeconds),
            'status' => 'en_route',
        ]);

        return back()->with('success', "Merchants dispatched to {$targetVillage->name} ({$targetVillage->coordinates}).");
    }

    /**
     * Create a market trade offer.
     */
    public function createOffer(Request $request): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $sellResource = $request->input('sell_resource');
        $sellAmount = (int) $request->input('sell_amount');
        $buyResource = $request->input('buy_resource');
        $buyAmount = (int) $request->input('buy_amount');
        $maxTrades = max(1, min(100, (int) $request->input('max_trades', 1)));

        if (! in_array($sellResource, ['wood', 'clay', 'iron']) || ! in_array($buyResource, ['wood', 'clay', 'iron'])) {
            return back()->with('error', 'Invalid resource types.');
        }

        if ($sellResource === $buyResource) {
            return back()->with('error', 'Cannot trade identical resources.');
        }

        if ($sellAmount <= 0 || $buyAmount <= 0) {
            return back()->with('error', 'Amounts must be greater than 0.');
        }

        $ratio = $buyAmount / $sellAmount;
        if ($ratio < 0.5 || $ratio > 2.0) {
            return back()->with('error', 'Trade ratio must be between 0.50 and 2.00.');
        }

        $totalSellRequired = $sellAmount * $maxTrades;
        $merchantCapacity = config('game.buildings.market.merchant_capacity', 1000);
        $merchantsPerTrade = (int) ceil($sellAmount / $merchantCapacity);
        $merchantsTotal = $merchantsPerTrade * $maxTrades;

        if ($village->available_merchants < $merchantsTotal) {
            return back()->with('error', "Not enough merchants available ({$village->available_merchants} available, {$merchantsTotal} needed).");
        }

        $this->resourceService->accrueResources($village);
        $woodCost = $sellResource === 'wood' ? $totalSellRequired : 0;
        $clayCost = $sellResource === 'clay' ? $totalSellRequired : 0;
        $ironCost = $sellResource === 'iron' ? $totalSellRequired : 0;

        if (! $this->resourceService->canAfford($village, $woodCost, $clayCost, $ironCost)) {
            return back()->with('error', 'Not enough resources to create this offer.');
        }

        $this->resourceService->deductResources($village, $woodCost, $clayCost, $ironCost);

        MarketOffer::create([
            'village_id' => $village->id,
            'user_id' => $village->user_id,
            'sell_resource' => $sellResource,
            'sell_amount' => $sellAmount,
            'buy_resource' => $buyResource,
            'buy_amount' => $buyAmount,
            'max_trades' => $maxTrades,
            'trades_completed' => 0,
            'status' => 'active',
        ]);

        return back()->with('success', 'Market offer created successfully.');
    }

    /**
     * Accept another player's market offer.
     */
    public function acceptOffer(Request $request, int $offerId): RedirectResponse
    {
        /** @var Village $buyerVillage */
        $buyerVillage = $request->attributes->get('currentVillage');

        $offer = MarketOffer::where('id', $offerId)->where('status', 'active')->first();
        if (! $offer) {
            return back()->with('error', 'Offer no longer available.');
        }

        if ($offer->village_id === $buyerVillage->id) {
            return back()->with('error', 'Cannot accept your own offer.');
        }

        $tradesToAccept = max(1, min($offer->remaining_trades, (int) $request->input('count', 1)));
        $merchantCapacity = config('game.buildings.market.merchant_capacity', 1000);
        $merchantsNeeded = (int) ceil(($offer->buy_amount * $tradesToAccept) / $merchantCapacity);

        if ($buyerVillage->available_merchants < $merchantsNeeded) {
            return back()->with('error', "Not enough merchants available ({$buyerVillage->available_merchants} available, {$merchantsNeeded} needed).");
        }

        $totalBuyerGives = $offer->buy_amount * $tradesToAccept;
        $woodCost = $offer->buy_resource === 'wood' ? $totalBuyerGives : 0;
        $clayCost = $offer->buy_resource === 'clay' ? $totalBuyerGives : 0;
        $ironCost = $offer->buy_resource === 'iron' ? $totalBuyerGives : 0;

        $this->resourceService->accrueResources($buyerVillage);
        if (! $this->resourceService->canAfford($buyerVillage, $woodCost, $clayCost, $ironCost)) {
            return back()->with('error', 'Not enough resources to accept this trade.');
        }

        // Deduct payment from buyer
        $this->resourceService->deductResources($buyerVillage, $woodCost, $clayCost, $ironCost);

        $sellerVillage = $offer->village;
        $distance = $buyerVillage->distanceTo($sellerVillage);
        $speedMinutesPerField = config('game.buildings.market.merchant_speed', 6);
        $worldSpeed = config('game.world.speed', 1.0);
        $durationSeconds = max(10, (int) round(($distance * $speedMinutesPerField * 60) / $worldSpeed));

        // Transport 1: Buyer sends payment to Seller
        MarketTransport::create([
            'source_village_id' => $buyerVillage->id,
            'target_village_id' => $sellerVillage->id,
            'wood' => $woodCost,
            'clay' => $clayCost,
            'iron' => $ironCost,
            'merchants' => $merchantsNeeded,
            'departs_at' => now(),
            'arrives_at' => now()->addSeconds($durationSeconds),
            'status' => 'en_route',
        ]);

        // Transport 2: Seller goods transported to Buyer
        $totalGoods = $offer->sell_amount * $tradesToAccept;
        $goodsWood = $offer->sell_resource === 'wood' ? $totalGoods : 0;
        $goodsClay = $offer->sell_resource === 'clay' ? $totalGoods : 0;
        $goodsIron = $offer->sell_resource === 'iron' ? $totalGoods : 0;
        $sellerMerchants = (int) ceil($totalGoods / $merchantCapacity);

        MarketTransport::create([
            'source_village_id' => $sellerVillage->id,
            'target_village_id' => $buyerVillage->id,
            'wood' => $goodsWood,
            'clay' => $goodsClay,
            'iron' => $goodsIron,
            'merchants' => $sellerMerchants,
            'departs_at' => now(),
            'arrives_at' => now()->addSeconds($durationSeconds),
            'status' => 'en_route',
        ]);

        $offer->trades_completed += $tradesToAccept;
        if ($offer->trades_completed >= $offer->max_trades) {
            $offer->status = 'completed';
        }
        $offer->save();

        return back()->with('success', 'Trade accepted! Merchants are on their way.');
    }

    /**
     * Cancel an active market offer and refund resources.
     */
    public function cancelOffer(Request $request, int $offerId): RedirectResponse
    {
        /** @var Village $village */
        $village = $request->attributes->get('currentVillage');

        $offer = MarketOffer::where('id', $offerId)
            ->where('village_id', $village->id)
            ->where('status', 'active')
            ->first();

        if (! $offer) {
            return back()->with('error', 'Offer not found.');
        }

        $remainingTrades = $offer->remaining_trades;
        $refundAmount = $offer->sell_amount * $remainingTrades;

        $woodRefund = $offer->sell_resource === 'wood' ? $refundAmount : 0;
        $clayRefund = $offer->sell_resource === 'clay' ? $refundAmount : 0;
        $ironRefund = $offer->sell_resource === 'iron' ? $refundAmount : 0;

        $this->resourceService->addResources($village, $woodRefund, $clayRefund, $ironRefund);

        $offer->status = 'cancelled';
        $offer->save();

        return back()->with('success', 'Offer cancelled and resources returned to warehouse.');
    }
}
