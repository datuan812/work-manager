<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Achievement;

class AchievementController extends Controller
{
    public function index()
    {
        return Achievement::query()->withCount('users')->orderBy('id')->get();
    }
}
