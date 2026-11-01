<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $users = DB::table('shelves')->select('user_id')->distinct()->pluck('user_id');

            foreach ($users as $userId) {
                $shelves = DB::table('shelves')
                    ->where('user_id', $userId)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get();

                $target = $shelves->firstWhere('name', 'En biblioteca');

                if ($target === null) {
                    continue;
                }

                $ordered = $shelves
                    ->reject(fn ($shelf) => $shelf->id === $target->id)
                    ->values()
                    ->prepend($target);

                foreach ($ordered as $position => $shelf) {
                    DB::table('shelves')
                        ->where('id', $shelf->id)
                        ->update(['position' => $position]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $users = DB::table('shelves')->select('user_id')->distinct()->pluck('user_id');

            foreach ($users as $userId) {
                $shelves = DB::table('shelves')
                    ->where('user_id', $userId)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get();

                $target = $shelves->firstWhere('name', 'En biblioteca');

                if ($target === null) {
                    continue;
                }

                $ordered = $shelves
                    ->reject(fn ($shelf) => $shelf->id === $target->id)
                    ->values()
                    ->push($target);

                foreach ($ordered as $position => $shelf) {
                    DB::table('shelves')
                        ->where('id', $shelf->id)
                        ->update(['position' => $position]);
                }
            }
        });
    }
};
