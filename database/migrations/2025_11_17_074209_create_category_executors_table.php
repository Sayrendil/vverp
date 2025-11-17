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
        Schema::create('category_executors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_category_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('Активен как исполнитель');
            $table->integer('priority')->default(0)->comment('Приоритет для авто-назначения (выше = приоритетнее)');
            $table->integer('max_tickets')->default(10)->comment('Максимум одновременных заявок');
            $table->timestamps();

            // Уникальный индекс - пользователь не может быть добавлен дважды в одну категорию
            $table->unique(['user_id', 'ticket_category_id']);

            // Индексы для быстрого поиска
            $table->index('ticket_category_id');
            $table->index(['is_active', 'ticket_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_executors');
    }
};
