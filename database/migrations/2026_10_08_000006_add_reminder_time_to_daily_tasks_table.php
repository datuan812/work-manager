<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->time('reminder_time')->nullable()->after('date');
            $table->index(['date', 'reminder_time']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropIndex(['date', 'reminder_time']);
            $table->dropColumn('reminder_time');
        });
    }
};
