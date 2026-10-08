<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE reward_redemptions MODIFY redeemed_at timestamp NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reward_redemptions MODIFY redeemed_at timestamp NOT NULL');
    }
};
