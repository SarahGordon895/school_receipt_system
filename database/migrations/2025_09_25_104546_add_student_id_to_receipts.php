<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('receipt_no')->constrained('students')->nullOnDelete();
            // keep existing student_name column as printed snapshot
        });
    }
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
