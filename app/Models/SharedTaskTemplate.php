<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SharedTaskTemplate extends Model
{
    protected $fillable = ['category_id', 'title', 'description', 'icon', 'points', 'min_age', 'max_age', 'steps', 'is_active'];

    protected function casts(): array
    {
        return ['points' => 'integer', 'min_age' => 'integer', 'max_age' => 'integer', 'steps' => 'array', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class);
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'shared_task_template_imports');
    }
}
