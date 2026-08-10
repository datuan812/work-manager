<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'icon', 'required_points', 'is_active'])]
class Reward extends Model
{
    protected function casts(): array
    {
        return ['required_points' => 'integer', 'is_active' => 'boolean'];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}
