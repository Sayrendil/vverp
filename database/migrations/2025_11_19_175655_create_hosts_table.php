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
        Schema::create('hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Название хоста (Касса 1, Касса 2, Сервер, и т.д.)
            $table->string('ip_address'); // IP адрес или hostname для проверки
            $table->text('description')->nullable(); // Описание хоста
            $table->boolean('is_active')->default(true); // Активен ли мониторинг
            $table->integer('check_interval')->default(5); // Интервал проверки в минутах
            $table->integer('timeout')->default(3); // Таймаут пинга в секундах
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['store_id', 'is_active']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosts');
    }
};
