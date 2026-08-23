<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketOffer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sell_amount' => 'integer',
            'buy_amount' => 'integer',
            'max_trades' => 'integer',
            'trades_completed' => 'integer',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRatioAttribute(): float
    {
        if ($this->sell_amount == 0) {
            return 1.0;
        }

        return round($this->buy_amount / $this->sell_amount, 2);
    }

    public function getRemainingTradesAttribute(): int
    {
        return max(0, $this->max_trades - $this->trades_completed);
    }
}
