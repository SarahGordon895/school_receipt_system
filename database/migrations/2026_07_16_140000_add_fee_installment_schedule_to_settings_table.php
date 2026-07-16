<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('fee_installment_day')->default(15)->after('bank_crdb_account_number');
            $table->json('fee_installment_months')->nullable()->after('fee_installment_day');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['fee_installment_day', 'fee_installment_months']);
        });
    }
};
