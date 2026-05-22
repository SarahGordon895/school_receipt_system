<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('sms_enabled')->default(false)->after('receipt_footer');
            $table->boolean('sms_simulate')->default(true)->after('sms_enabled');
            $table->string('sms_api_endpoint')->nullable()->after('sms_simulate');
            $table->string('sms_api_token')->nullable()->after('sms_api_endpoint');
            $table->string('sms_sender_id', 32)->nullable()->after('sms_api_token');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_enabled',
                'sms_simulate',
                'sms_api_endpoint',
                'sms_api_token',
                'sms_sender_id',
            ]);
        });
    }
};
