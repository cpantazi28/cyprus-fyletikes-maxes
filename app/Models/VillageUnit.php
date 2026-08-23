<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageUnit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'spear' => 'integer',
            'sword' => 'integer',
            'axe' => 'integer',
            'archer' => 'integer',
            'spy' => 'integer',
            'light' => 'integer',
            'marcher' => 'integer',
            'heavy' => 'integer',
            'ram' => 'integer',
            'catapult' => 'integer',
            'knight' => 'integer',
            'snob' => 'integer',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function toArrayOfUnits(): array
    {
        return [
            'spear' => $this->spear,
            'sword' => $this->sword,
            'axe' => $this->axe,
            'archer' => $this->archer,
            'spy' => $this->spy,
            'light' => $this->light,
            'marcher' => $this->marcher,
            'heavy' => $this->heavy,
            'ram' => $this->ram,
            'catapult' => $this->catapult,
            'knight' => $this->knight,
            'snob' => $this->snob,
        ];
    }
}
