<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('draw_date');
            $table->string('card_key', 64);
            $table->unsignedTinyInteger('ball_index');
            $table->boolean('duplicate')->default(false);
            $table->unsignedTinyInteger('ink_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'draw_date']);
            $table->index('card_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_draws');
    }
};
