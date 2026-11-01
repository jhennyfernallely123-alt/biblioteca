<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->boolean('is_fixed')->default(false)->after('name');
        });

        // Marcar como fijas las estanterías ya existentes y agregar "En biblioteca"
        DB::transaction(function () {
            $users = DB::table('users')->pluck('id');

            foreach ($users as $userId) {
                // Marcar todas las existentes como fijas
                DB::table('shelves')
                    ->where('user_id', $userId)
                    ->update(['is_fixed' => true]);

                // Agregar "En biblioteca" si no existe
                $exists = DB::table('shelves')
                    ->where('user_id', $userId)
                    ->where('name', 'En biblioteca')
                    ->exists();

                if (! $exists) {
                    $maxPosition = DB::table('shelves')
                        ->where('user_id', $userId)
                        ->max('position');

                    $position = $maxPosition === null ? 0 : (int) $maxPosition + 1;

                    DB::table('shelves')->insert([
                        'user_id' => $userId,
                        'name' => 'En biblioteca',
                        'position' => $position,
                        'view_mode' => 'covers',
                        'is_fixed' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->dropColumn('is_fixed');
        });
    }
};
