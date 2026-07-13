<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('bank', 16)->nullable();
            $table->unsignedBigInteger('extracted_amount')->nullable();
            $table->string('extracted_reference')->nullable();
            $table->date('extracted_payment_date')->nullable();
            $table->string('extracted_account_number', 32)->nullable();
            $table->text('extracted_raw_text')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('verification_message')->nullable();
            $table->foreignId('receipt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('extracted_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_payment_submissions');
    }
};
