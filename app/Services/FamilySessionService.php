<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Support\Facades\DB;

class FamilySessionService
{
    // Call with the family row locked in an open transaction.
    public function revoke(Family $family): void
    {
        $family->increment('access_version');
        $family->users()->withoutGlobalScope('family')->update(['remember_token' => null]);

        DB::table('parent_pin_resets')->whereIn('user_id', $family->users()->withoutGlobalScope('family')->select('id'))->delete();
    }
}
