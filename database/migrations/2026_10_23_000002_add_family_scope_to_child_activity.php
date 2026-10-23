<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['daily_task_submissions', 'daily_task_drafts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('family_id')->nullable()->after('id')->index();
                $table->foreign('family_id')->references('id')->on('families')->nullOnDelete();
            });

            DB::statement("UPDATE {$tableName} SET family_id = (SELECT family_id FROM users WHERE users.id = {$tableName}.user_id) WHERE family_id IS NULL");
        }
    }

    public function down(): void
    {
        foreach (['daily_task_submissions', 'daily_task_drafts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['family_id']);
                $table->dropColumn('family_id');
            });
        }
    }
};
