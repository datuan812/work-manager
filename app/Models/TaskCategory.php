<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToFamily;

#[Fillable(['family_id', 'name', 'icon', 'color', 'sort_order'])]
class TaskCategory extends Model
{
    use BelongsToFamily;
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'category_id');
    }
}
