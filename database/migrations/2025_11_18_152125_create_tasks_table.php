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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->integer('task_number');

            // Основная информация
            $table->string('title', 500);
            $table->text('description')->nullable();

            // Классификация
            $table->enum('type', ['task', 'bug', 'feature', 'improvement'])->default('task');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('medium');
            $table->foreignId('status_id')->constrained('task_statuses')->onDelete('restrict');

            // Участники
            $table->foreignId('reporter_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');

            // Иерархия
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('cascade');

            // Временные метки
            $table->date('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Оценки
            $table->tinyInteger('story_points')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();

            // Позиция на доске
            $table->integer('board_position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->unique(['project_id', 'task_number']);
            $table->index('project_id');
            $table->index('assignee_id');
            $table->index('status_id');
            $table->index('parent_task_id');
            $table->index('due_date');
            $table->index(['status_id', 'board_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
