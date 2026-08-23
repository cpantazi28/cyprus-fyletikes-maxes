<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Village extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Village $village) {
            if ($village->x !== null && $village->y !== null) {
                $kY = (int) floor($village->y / 100);
                $kX = (int) floor($village->x / 100);
                $village->attributes['continent'] = 'K'.($kY * 10 + $kX);
            }
        });

        static::updating(function (Village $village) {
            if ($village->x !== null && $village->y !== null) {
                $kY = (int) floor($village->y / 100);
                $kX = (int) floor($village->x / 100);
                $village->attributes['continent'] = 'K'.($kY * 10 + $kX);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
            'points' => 'integer',
            'loyalty' => 'float',
            'wood' => 'float',
            'clay' => 'float',
            'iron' => 'float',
            'coins_minted' => 'integer',
            'last_loyalty_update' => 'datetime',
            'last_resource_update' => 'datetime',
            'main' => 'integer',
            'barracks' => 'integer',
            'stable' => 'integer',
            'garage' => 'integer',
            'snob' => 'integer',
            'smith' => 'integer',
            'place' => 'integer',
            'market' => 'integer',
            'wood_level' => 'integer',
            'stone_level' => 'integer',
            'iron_level' => 'integer',
            'farm_level' => 'integer',
            'storage_level' => 'integer',
            'hide_level' => 'integer',
            'wall_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function units(): HasOne
    {
        return $this->hasOne(VillageUnit::class);
    }

    public function buildingQueues(): HasMany
    {
        return $this->hasMany(BuildingQueue::class)->orderBy('starts_at');
    }

    public function activeBuildingQueues(): HasMany
    {
        return $this->hasMany(BuildingQueue::class)
            ->where('status', 'in_progress')
            ->orderBy('starts_at');
    }

    public function recruitmentQueues(): HasMany
    {
        return $this->hasMany(RecruitmentQueue::class)->orderBy('starts_at');
    }

    public function activeRecruitmentQueues(): HasMany
    {
        return $this->hasMany(RecruitmentQueue::class)
            ->where('status', 'in_progress')
            ->orderBy('starts_at');
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(Movement::class, 'source_village_id')
            ->where('status', 'en_route')
            ->orderBy('arrives_at');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(Movement::class, 'target_village_id')
            ->where('status', 'en_route')
            ->orderBy('arrives_at');
    }

    public function incomingAttacks(): HasMany
    {
        return $this->hasMany(Movement::class, 'target_village_id')
            ->where('status', 'en_route')
            ->where('type', 'attack')
            ->orderBy('arrives_at');
    }

    public function stationedSupports(): HasMany
    {
        return $this->hasMany(VillageSupport::class, 'stationed_village_id');
    }

    public function supportsOut(): HasMany
    {
        return $this->hasMany(VillageSupport::class, 'home_village_id');
    }

    public function marketTransportsOut(): HasMany
    {
        return $this->hasMany(MarketTransport::class, 'source_village_id')
            ->where('status', 'en_route')
            ->orderBy('arrives_at');
    }

    public function marketTransportsIn(): HasMany
    {
        return $this->hasMany(MarketTransport::class, 'target_village_id')
            ->where('status', 'en_route')
            ->orderBy('arrives_at');
    }

    public function marketOffers(): HasMany
    {
        return $this->hasMany(MarketOffer::class)->where('status', 'active');
    }

    // Helper: Coordinates string e.g. 500|500
    public function getCoordinatesAttribute(): string
    {
        return "{$this->x}|{$this->y}";
    }

    // Helper: Storage capacity
    public function getStorageCapacityAttribute(): int
    {
        $capacities = config('game.buildings.storage.capacities', []);

        return $capacities[$this->storage_level] ?? 1000;
    }

    // Helper: Hideout capacity
    public function getHideCapacityAttribute(): int
    {
        $capacities = config('game.buildings.hide.capacities', []);

        return $capacities[$this->hide_level] ?? 0;
    }

    // Helper: Farm max population
    public function getMaxPopulationAttribute(): int
    {
        $capacities = config('game.buildings.farm.capacities', []);

        return $capacities[$this->farm_level] ?? 240;
    }

    // Helper: Farm used population (buildings + stationed units + recruit queues)
    public function getUsedPopulationAttribute(): int
    {
        $pop = 0;

        // Buildings pop
        $buildings = config('game.buildings', []);
        foreach ($buildings as $key => $data) {
            $level = $this->getBuildingLevel($key);
            if ($level > 0 && isset($data['base_pop'])) {
                $pop += (int) round($data['base_pop'] * pow($data['pop_factor'] ?? 1.17, $level - 1));
            }
        }

        // Units pop
        if ($this->relationLoaded('units') && $this->units) {
            $unitDefs = config('game.units', []);
            foreach ($unitDefs as $uKey => $uData) {
                $count = $this->units->{$uKey} ?? 0;
                $pop += $count * ($uData['pop'] ?? 1);
            }
        }

        // Active recruitment queue pop
        foreach ($this->activeRecruitmentQueues as $rq) {
            $remaining = $rq->count - $rq->count_finished;
            $unitPop = config("game.units.{$rq->unit_type}.pop", 1);
            $pop += $remaining * $unitPop;
        }

        return $pop;
    }

    public function getFreePopulationAttribute(): int
    {
        return max(0, $this->max_population - $this->used_population);
    }

    // Helper: Production rates per hour
    public function getWoodProductionPerHourAttribute(): int
    {
        $speed = config('game.world.speed', 1.0);
        $prod = config('game.buildings.wood.production')[$this->wood_level] ?? 30;

        return (int) round($prod * $speed);
    }

    public function getClayProductionPerHourAttribute(): int
    {
        $speed = config('game.world.speed', 1.0);
        $prod = config('game.buildings.stone.production')[$this->stone_level] ?? 30;

        return (int) round($prod * $speed);
    }

    public function getIronProductionPerHourAttribute(): int
    {
        $speed = config('game.world.speed', 1.0);
        $prod = config('game.buildings.iron.production')[$this->iron_level] ?? 30;

        return (int) round($prod * $speed);
    }

    // Helper: Merchants available
    public function getAvailableMerchantsAttribute(): int
    {
        $total = config("game.buildings.market.merchants.{$this->market}", 0);

        $usedInTransports = $this->marketTransportsOut->sum('merchants');

        $usedInOffers = 0;
        foreach ($this->marketOffers as $offer) {
            $remainingTrades = $offer->max_trades - $offer->trades_completed;
            $neededPerTrade = (int) ceil($offer->sell_amount / config('game.buildings.market.merchant_capacity', 1000));
            $usedInOffers += $remainingTrades * $neededPerTrade;
        }

        return max(0, $total - $usedInTransports - $usedInOffers);
    }

    public function getTotalMerchantsAttribute(): int
    {
        return config("game.buildings.market.merchants.{$this->market}", 0);
    }

    public function getBuildingLevel(string $building): int
    {
        $val = match ($building) {
            'wood' => $this->wood_level,
            'stone' => $this->stone_level,
            'iron' => $this->iron_level,
            'farm' => $this->farm_level,
            'storage' => $this->storage_level,
            'hide' => $this->hide_level,
            'wall' => $this->wall_level,
            default => $this->{$building} ?? 0,
        };

        return (int) ($val ?? 0);
    }

    public function setBuildingLevel(string $building, int $level): void
    {
        match ($building) {
            'wood' => $this->wood_level = $level,
            'stone' => $this->stone_level = $level,
            'iron' => $this->iron_level = $level,
            'farm' => $this->farm_level = $level,
            'storage' => $this->storage_level = $level,
            'hide' => $this->hide_level = $level,
            'wall' => $this->wall_level = $level,
            default => $this->{$building} = $level,
        };
    }

    // Calculate total village points based on building levels
    public function calculatePoints(): int
    {
        $total = 0;
        $buildingConfigs = config('game.buildings', []);

        foreach ($buildingConfigs as $key => $data) {
            $lvl = $this->getBuildingLevel($key);
            if ($lvl > 0 && isset($data['points'][$lvl])) {
                $total += $data['points'][$lvl];
            }
        }

        return max(26, $total);
    }

    // Distance helper
    public function distanceTo(Village $target): float
    {
        $dx = $this->x - $target->x;
        $dy = $this->y - $target->y;

        return round(sqrt($dx * $dx + $dy * $dy), 2);
    }
}
