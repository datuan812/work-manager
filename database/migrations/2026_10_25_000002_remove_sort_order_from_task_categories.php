<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('task_categories', 'sort_order')) {
            Schema::table('task_categories', function (Blueprint $table): void {
                $table->dropColumn('sort_order');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('task_categories', 'sort_order')) {
            Schema::table('task_categories', function (Blueprint $table): void {
                $table->unsignedInteger('sort_order')->default(0);
            });
        }
    }
};
