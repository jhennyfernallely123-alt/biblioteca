<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BOOKS_PER_BAND = 5;

    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedInteger('band')->default(0)->after('position');
            $table->unsignedInteger('band_position')->default(0)->after('band');
        });

        $books = DB::table('books')
            ->join('shelves', 'shelves.id', '=', 'books.shelf_id')
            ->orderBy('shelves.position')
            ->orderBy('shelves.id')
            ->orderBy('books.position')
            ->orderBy('books.title')
            ->orderBy('books.id')
            ->select('books.id')
            ->get();

        $band = 0;
        $position = 0;

        foreach ($books as $book) {
            DB::table('books')->where('id', $book->id)->update([
                'band' => $band,
                'band_position' => $position,
            ]);

            $position++;

            if ($position === self::BOOKS_PER_BAND) {
                $band++;
                $position = 0;
            }
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['band', 'band_position']);
        });
    }
};
