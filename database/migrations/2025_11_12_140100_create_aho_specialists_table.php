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
        Schema::create('aho_specialists', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Имя специалиста
            $table->string('phone')->nullable(); // Телефон для звонков
            $table->string('telegram_id')->nullable(); // ID телеграм для отправки уведомлений
            $table->string('telegram_username')->nullable(); // Username в телеграм
            $table->string('specialization'); // Специализация: сантехника, холодильники, электрика и т.д.
            $table->boolean('is_active')->default(true); // Активен ли специалист
            $table->text('notes')->nullable(); // Примечания
            $table->timestamps();

            $table->index('telegram_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aho_specialists');
    }
};
