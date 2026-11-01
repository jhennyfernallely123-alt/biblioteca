<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('view_mode')->default('covers')->after('shelf_id');
        });

        DB::transaction(function () {
            $modes = DB::table('shelves')->pluck('view_mode', 'id');

            foreach ($modes as $shelfId => $mode) {
                DB::table('books')
                    ->where('shelf_id', $shelfId)
                    ->update(['view_mode' => $mode ?: 'covers']);
            }
        });

        Schema::table('shelves', function (Blueprint $table) {
            $table->dropColumn('view_mode');
        });
    }

    public function down(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->string('view_mode')->default('covers')->after('position');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('view_mode');
        });
    }
};
