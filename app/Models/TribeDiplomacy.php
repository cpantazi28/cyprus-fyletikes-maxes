<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TribeDiplomacy extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function tribe(): BelongsTo
    {
        return $this->belongsTo(Tribe::class);
    }

    public function targetTribe(): BelongsTo
    {
        return $this->belongsTo(Tribe::class, 'target_tribe_id');
    }
}
