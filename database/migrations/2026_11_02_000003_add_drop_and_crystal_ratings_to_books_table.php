<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedTinyInteger('drop_rating')->nullable()->after('heart_rating');
            $table->unsignedTinyInteger('crystal_rating')->nullable()->after('drop_rating');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['drop_rating', 'crystal_rating']);
        });
    }
};
