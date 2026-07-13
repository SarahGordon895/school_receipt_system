<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('bank_nmb_account_name')->nullable()->after('receipt_footer');
            $table->string('bank_nmb_account_number', 32)->nullable()->after('bank_nmb_account_name');
            $table->string('bank_crdb_account_name')->nullable()->after('bank_nmb_account_number');
            $table->string('bank_crdb_account_number', 32)->nullable()->after('bank_crdb_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_nmb_account_name',
                'bank_nmb_account_number',
                'bank_crdb_account_name',
                'bank_crdb_account_number',
            ]);
        });
    }
};
