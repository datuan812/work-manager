<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('points_spent')->index();
            $table->timestamp('requested_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('requested_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });

        Schema::table('reward_redemptions', function (Blueprint $table) {
            $table->index(['status', 'requested_at']);
        });

        DB::table('reward_redemptions')
            ->whereNull('requested_at')
            ->update([
                'status' => 'approved',
                'requested_at' => DB::raw('redeemed_at'),
                'approved_at' => DB::raw('redeemed_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'requested_at']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'requested_at', 'approved_at', 'rejected_at']);
        });
    }
};
