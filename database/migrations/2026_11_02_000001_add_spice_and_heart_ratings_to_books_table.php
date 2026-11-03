<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedTinyInteger('spice_rating')->nullable()->after('rating');
            $table->unsignedTinyInteger('heart_rating')->nullable()->after('spice_rating');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['spice_rating', 'heart_rating']);
        });
    }
};
