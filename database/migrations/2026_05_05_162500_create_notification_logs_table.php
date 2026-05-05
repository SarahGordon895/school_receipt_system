<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('channel'); // email|sms
            $table->string('status'); // sent|failed|skipped
            $table->date('sent_on');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'channel', 'sent_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
