<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', UserRole::CHILD->value)
            ->whereNull('child_pin')
            ->update(['child_pin' => Hash::make('1234')]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', UserRole::CHILD->value)
            ->update(['child_pin' => null]);
    }
};
