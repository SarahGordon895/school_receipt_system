<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_parent_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 32)->default('Guardian');
            $table->boolean('is_primary')->default(true);
            $table->string('parent_phone')->nullable();
            $table->foreignId('linked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'parent_user_id']);
            $table->index(['parent_user_id', 'is_primary']);
        });

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'admitted_at')) {
                $table->timestamp('admitted_at')->nullable()->after('expected_total_fee');
            }
            if (! Schema::hasColumn('students', 'registered_by_user_id')) {
                $table->foreignId('registered_by_user_id')->nullable()->after('admitted_at')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'parent_user_id')) {
            $students = DB::table('students')->whereNotNull('parent_user_id')->get(['id', 'parent_user_id', 'parent_phone', 'created_at']);
            foreach ($students as $row) {
                $exists = DB::table('student_parent_links')
                    ->where('student_id', $row->id)
                    ->where('parent_user_id', $row->parent_user_id)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('student_parent_links')->insert([
                    'student_id' => $row->id,
                    'parent_user_id' => $row->parent_user_id,
                    'relationship' => 'Guardian',
                    'is_primary' => true,
                    'parent_phone' => $row->parent_phone,
                    'linked_at' => $row->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'registered_by_user_id')) {
                $table->dropConstrainedForeignId('registered_by_user_id');
            }
            if (Schema::hasColumn('students', 'admitted_at')) {
                $table->dropColumn('admitted_at');
            }
        });

        Schema::dropIfExists('student_parent_links');
    }
};
