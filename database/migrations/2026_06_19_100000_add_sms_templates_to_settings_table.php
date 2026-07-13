<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('sms_template_payment_received')->nullable()->after('sms_sender_id');
            $table->text('sms_template_fee_reminder')->nullable()->after('sms_template_payment_received');
            $table->text('sms_template_overdue')->nullable()->after('sms_template_fee_reminder');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_template_payment_received',
                'sms_template_fee_reminder',
                'sms_template_overdue',
            ]);
        });
    }
};
