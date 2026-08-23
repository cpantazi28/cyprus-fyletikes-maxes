<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TribeForumPost extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(TribeForumThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
