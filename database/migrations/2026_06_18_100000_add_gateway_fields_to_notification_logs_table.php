<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('gateway_uid')->nullable()->after('message');
            $table->string('delivery_status')->nullable()->after('gateway_uid');

            $table->index(['status', 'sent_on']);
            $table->index('gateway_uid');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'sent_on']);
            $table->dropIndex(['gateway_uid']);
            $table->dropColumn(['gateway_uid', 'delivery_status']);
        });
    }
};
