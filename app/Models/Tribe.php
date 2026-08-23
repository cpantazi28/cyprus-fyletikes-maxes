<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tribe extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'rank' => 'integer',
            'member_count' => 'integer',
        ];
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TribeMember::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(TribeForumThread::class)->orderByDesc('is_sticky')->orderByDesc('updated_at');
    }

    public function diplomacies(): HasMany
    {
        return $this->hasMany(TribeDiplomacy::class);
    }

    public function allies(): HasMany
    {
        return $this->hasMany(TribeDiplomacy::class)->where('type', 'ally');
    }

    public function enemies(): HasMany
    {
        return $this->hasMany(TribeDiplomacy::class)->where('type', 'enemy');
    }

    public function naps(): HasMany
    {
        return $this->hasMany(TribeDiplomacy::class)->where('type', 'nap');
    }

    public function recalculatePoints(): void
    {
        $userIds = $this->members()->pluck('user_id');
        $this->points = Village::whereIn('user_id', $userIds)->sum('points');
        $this->member_count = $userIds->count();
        $this->save();
    }
}
