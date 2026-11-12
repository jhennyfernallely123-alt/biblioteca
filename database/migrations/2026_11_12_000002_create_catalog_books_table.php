<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->string('author', 255)->nullable();
            $table->string('isbn', 20)->nullable()->unique();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_books');
    }
};
