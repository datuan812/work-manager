<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('daily_tasks')
            ->where('status', 'skipped')
            ->update(['status' => 'incomplete']);
    }

    public function down(): void
    {
        DB::table('daily_tasks')
            ->where('status', 'incomplete')
            ->update(['status' => 'skipped']);
    }
};
