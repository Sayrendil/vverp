<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('telegram_sessions', function (Blueprint $table) {
            // ID последнего сообщения бота для редактирования (single message wizard)
            $table->unsignedBigInteger('message_id')->nullable()->after('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_sessions', function (Blueprint $table) {
            $table->dropColumn('message_id');
        });
    }
};
