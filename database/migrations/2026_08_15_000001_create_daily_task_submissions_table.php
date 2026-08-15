<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_task_submissions');
    }
};
