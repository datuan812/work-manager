<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['code', 'title', 'description', 'icon', 'criteria', 'is_active'])]
class Achievement extends Model
{
    protected function casts(): array
    {
        return ['criteria' => 'array', 'is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
