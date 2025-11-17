<?php

use App\Models\Cash;
use App\Models\Problem;
use App\Models\Status;
use App\Models\Store;
use App\Models\User;
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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignIdFor(Status::class)->nullable();
            $table->foreignIdFor(Problem::class)->nullable();
            $table->foreignIdFor(Store::class)->nullable();
            $table->foreignIdFor(Cash::class)->nullable();
            $table->foreignIdFor(User::class, 'author_id')->nullable();
            $table->foreignIdFor(User::class, 'executor_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
