<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'date_of_birth', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function dailyTasks(): HasMany
    {
        return $this->hasMany(DailyTask::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function isParent(): bool
    {
        return $this->role === UserRole::PARENT;
    }

    public function isChild(): bool
    {
        return $this->role === UserRole::CHILD;
    }
}
