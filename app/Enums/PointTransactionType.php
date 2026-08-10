<?php

namespace App\Enums;

enum PointTransactionType: string
{
    case TASK_COMPLETED = 'task_completed';
    case TASK_UNCOMPLETED = 'task_uncompleted';
    case REWARD_REDEEMED = 'reward_redeemed';
    case ADJUSTMENT = 'adjustment';
}
