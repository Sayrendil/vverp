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
        Schema::create('host_availability_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available'); // Доступен ли хост
            $table->integer('response_time')->nullable(); // Время отклика в миллисекундах
            $table->integer('packet_loss')->default(0); // Процент потери пакетов (0-100)
            $table->text('error_message')->nullable(); // Сообщение об ошибке
            $table->timestamp('checked_at'); // Время проверки
            // Не используем timestamps, checked_at заменяет их

            // Индексы для быстрого поиска и аналитики
            $table->index(['host_id', 'checked_at']);
            $table->index(['host_id', 'is_available', 'checked_at']);
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('host_availability_logs');
    }
};
