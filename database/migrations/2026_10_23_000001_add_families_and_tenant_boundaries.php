<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('families')) {
            Schema::create('families', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code', 32)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        foreach (['task_categories', 'tasks', 'rewards', 'achievements', 'daily_tasks', 'reward_redemptions', 'point_transactions'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'family_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->unsignedBigInteger('family_id')->nullable()->after('id')->index();
                });
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->foreign('family_id', 'fk_'.$tableName.'_family')->references('id')->on('families')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'family_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('family_id')->nullable()->after('id')->index();
                $table->foreign('family_id', 'fk_users_family')->references('id')->on('families')->nullOnDelete();
            });
        }

        $familyId = DB::table('families')->insertGetId([
            'name' => 'Gia đình mặc định',
            'code' => 'GD'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->whereNull('family_id')->update(['family_id' => $familyId]);
        foreach (['task_categories', 'tasks', 'rewards', 'achievements', 'daily_tasks', 'reward_redemptions', 'point_transactions'] as $tableName) {
            DB::table($tableName)->whereNull('family_id')->update(['family_id' => $familyId]);
        }
    }

    public function down(): void
    {
        foreach (['task_categories', 'tasks', 'rewards', 'achievements', 'daily_tasks', 'reward_redemptions', 'point_transactions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['family_id']);
                $table->dropColumn('family_id');
            });
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['family_id']);
            $table->dropColumn('family_id');
        });
        Schema::dropIfExists('families');
    }
};
