<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('receipts')) {
            return;
        }

        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable()->change();
            }
            if (Schema::hasColumn('receipts', 'stream_id')) {
                $table->unsignedBigInteger('stream_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('receipts')) {
            return;
        }

        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::hasColumn('receipts', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('receipts', 'stream_id')) {
                $table->unsignedBigInteger('stream_id')->nullable(false)->change();
            }
        });
    }
};
