<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_draws', function (Blueprint $table) {
            $table->renameColumn('card_key', 'item_key');
        });

        Schema::table('card_draws', function (Blueprint $table) {
            $table->string('kind', 16)->default('card')->after('item_key');
        });
    }

    public function down(): void
    {
        Schema::table('card_draws', function (Blueprint $table) {
            $table->dropColumn('kind');
        });

        Schema::table('card_draws', function (Blueprint $table) {
            $table->renameColumn('item_key', 'card_key');
        });
    }
};
