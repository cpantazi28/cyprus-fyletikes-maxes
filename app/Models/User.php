<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class)->orderBy('name');
    }

    public function tribeMember(): HasOne
    {
        return $this->hasOne(TribeMember::class);
    }

    public function tribe(): HasOneThrough
    {
        return $this->hasOneThrough(Tribe::class, TribeMember::class, 'user_id', 'id', 'id', 'tribe_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class)->latest();
    }

    public function unreadReportsCount(): int
    {
        return $this->reports()->where('is_read', false)->count();
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id')
            ->where('is_deleted_by_recipient', false)
            ->latest();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id')
            ->where('is_deleted_by_sender', false)
            ->latest();
    }

    public function unreadMessagesCount(): int
    {
        return $this->hasMany(Message::class, 'recipient_id')
            ->where('is_deleted_by_recipient', false)
            ->where('is_read', false)
            ->count();
    }

    public function getTotalPointsAttribute(): int
    {
        return (int) $this->villages()->sum('points');
    }

    public function getVillageCountAttribute(): int
    {
        return $this->villages()->count();
    }
}
