<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('task_categories')
            ->orderBy('id')
            ->get()
            ->each(function (object $category): void {
                $globalCategory = DB::table('task_categories')
                    ->whereNull('family_id')
                    ->where('name', $category->name)
                    ->where('id', '!=', $category->id)
                    ->first();

                if ($globalCategory) {
                    DB::table('tasks')
                        ->where('category_id', $category->id)
                        ->update(['category_id' => $globalCategory->id]);
                    DB::table('task_categories')->where('id', $category->id)->delete();

                    return;
                }

                DB::table('task_categories')
                    ->where('id', $category->id)
                    ->update(['family_id' => null]);
            });
    }

    public function down(): void
    {
        // Global categories cannot be safely split back into family-specific rows.
    }
};
