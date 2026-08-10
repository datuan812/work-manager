<?php

namespace App\Enums;

enum RepeatType: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case CUSTOM_DAYS = 'custom_days';
}
