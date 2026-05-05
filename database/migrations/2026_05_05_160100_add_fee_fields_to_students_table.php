<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('admission_no')->nullable()->unique()->after('id');
            $table->string('class_name')->nullable()->after('name');
            $table->string('parent_name')->nullable()->after('class_name');
            $table->string('parent_phone')->nullable()->after('parent_name');
            $table->string('parent_email')->nullable()->index()->after('parent_phone');
            $table->date('fee_due_date')->nullable()->after('parent_email');
            $table->unsignedBigInteger('expected_total_fee')->default(0)->after('fee_due_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'admission_no',
                'class_name',
                'parent_name',
                'parent_phone',
                'parent_email',
                'fee_due_date',
                'expected_total_fee',
            ]);
        });
    }
};
