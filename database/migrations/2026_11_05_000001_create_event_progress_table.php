<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 64);
            $table->date('day_date');
            $table->string('task_key', 32);
            $table->unsignedTinyInteger('ink_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'event_key', 'day_date', 'task_key']);
            $table->index('event_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_progress');
    }
};
