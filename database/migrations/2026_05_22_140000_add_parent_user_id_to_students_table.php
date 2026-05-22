<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('parent_user_id')
                ->nullable()
                ->after('parent_email')
                ->constrained('users')
                ->nullOnDelete();
        });

        if (Schema::hasTable('students') && Schema::hasTable('users')) {
            $parents = DB::table('users')->where('role', 'parent')->pluck('id', 'email');

            foreach ($parents as $email => $userId) {
                DB::table('students')
                    ->whereNull('parent_user_id')
                    ->whereRaw('LOWER(parent_email) = ?', [strtolower((string) $email)])
                    ->update(['parent_user_id' => $userId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_user_id');
        });
    }
};
