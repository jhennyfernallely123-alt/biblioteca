<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decorations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shelf_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('color', 16);
            $table->foreignId('after_book_id')->nullable()->constrained('books')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['shelf_id', 'after_book_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decorations');
    }
};
