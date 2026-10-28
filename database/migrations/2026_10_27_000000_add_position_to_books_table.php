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
            $table->unsignedInteger('position')->default(0)->after('shelf_id');
            $table->index(['shelf_id', 'position']);
        });

        $positions = [];

        DB::table('books')
            ->select('id', 'shelf_id')
            ->orderBy('shelf_id')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->each(function ($book) use (&$positions) {
                $positions[$book->shelf_id] = ($positions[$book->shelf_id] ?? -1) + 1;

                DB::table('books')
                    ->where('id', $book->id)
                    ->update(['position' => $positions[$book->shelf_id]]);
            });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['shelf_id', 'position']);
            $table->dropColumn('position');
        });
    }
};
