<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['family_id', 'name', 'email', 'password', 'child_pin', 'parent_access_pin', 'parent_access_pin_enabled', 'role', 'avatar', 'date_of_birth', 'is_active'])]
#[Hidden(['password', 'child_pin', 'parent_access_pin', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::addGlobalScope('family', function (Builder $query): void {
            // Do not resolve auth()->user() here: User is also the model Laravel
            // uses to restore the session, which would recursively resolve auth.
            $familyId = session()->get('family_id');
            if ($familyId !== null) {
                $query->where($query->getModel()->getTable().'.family_id', $familyId);
            }
        });
    }

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
            'child_pin' => 'hashed',
            'parent_access_pin' => 'hashed',
            'parent_access_pin_enabled' => 'boolean',
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

    public function dailyTaskSubmissions(): HasMany
    {
        return $this->hasMany(DailyTaskSubmission::class);
    }

    public function dailyTaskDrafts(): HasMany
    {
        return $this->hasMany(DailyTaskDraft::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function isParent(): bool
    {
        return $this->role === UserRole::PARENT;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isInFamily(User $user): bool
    {
        return $this->family_id !== null && $this->family_id === $user->family_id;
    }

    public function family(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function isChild(): bool
    {
        return $this->role === UserRole::CHILD;
    }
}
