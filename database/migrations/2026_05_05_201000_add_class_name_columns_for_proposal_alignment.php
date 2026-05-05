<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('receipts') && !Schema::hasColumn('receipts', 'class_name')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->string('class_name')->nullable()->after('student_name');
            });
        }

        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'class_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('class_name')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('fee_structures') && !Schema::hasColumn('fee_structures', 'class_name')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->string('class_name')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'class_name')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->dropColumn('class_name');
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'class_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('class_name');
            });
        }

        if (Schema::hasTable('fee_structures') && Schema::hasColumn('fee_structures', 'class_name')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->dropColumn('class_name');
            });
        }
    }
};
