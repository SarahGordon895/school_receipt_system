<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->string('student_name');
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('stream_id')->constrained('streams')->restrictOnDelete();
            $table->unsignedBigInteger('amount'); // store in TZS (no decimals)
            $table->date('payment_date');
            $table->enum('payment_mode', ['Cash', 'Bank', 'Mobile Money', 'Other']);
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
