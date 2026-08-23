<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TribeForumThread extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_sticky' => 'boolean',
            'is_locked' => 'boolean',
            'views' => 'integer',
        ];
    }

    public function tribe(): BelongsTo
    {
        return $this->belongsTo(Tribe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(TribeForumPost::class, 'thread_id')->orderBy('created_at');
    }

    public function lastPost()
    {
        return $this->hasOne(TribeForumPost::class, 'thread_id')->latestOfMany();
    }
}
