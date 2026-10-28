<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_id')->constrained()->restrictOnDelete();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'shelf_id']);
            $table->index(['user_id', 'isbn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
