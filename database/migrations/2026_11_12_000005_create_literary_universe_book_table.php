<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('literary_universe_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('literary_universe_id')->constrained('literary_universes')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedSmallInteger('reading_order')->default(1);
            $table->timestamps();

            $table->unique(['literary_universe_id', 'book_id']);
            $table->index(['literary_universe_id', 'reading_order']);
            $table->index(['book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('literary_universe_book');
    }
};
