<?php

namespace App\Enums;

enum DailyTaskStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case INCOMPLETE = 'incomplete';
}
