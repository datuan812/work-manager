<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToFamily
{
    protected static function bootBelongsToFamily(): void
    {
        static::addGlobalScope('family', function (Builder $query): void {
            $familyId = session()->get('family_id');
            if ($familyId !== null) {
                $query->where($query->getModel()->getTable().'.family_id', $familyId);
            }
        });
    }
}
