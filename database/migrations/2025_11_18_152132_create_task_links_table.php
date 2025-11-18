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
        Schema::create('task_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('target_task_id')->constrained('tasks')->onDelete('cascade');
            $table->enum('link_type', ['blocks', 'relates', 'duplicates', 'depends_on'])->default('relates');
            $table->timestamp('created_at')->nullable();

            $table->unique(['source_task_id', 'target_task_id', 'link_type'], 'unique_task_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_links');
    }
};
