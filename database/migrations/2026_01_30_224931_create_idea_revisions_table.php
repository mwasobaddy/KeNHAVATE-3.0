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
        Schema::create('idea_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('revision_number');
            $table->json('changes'); // Store what changed
            $table->text('change_summary')->nullable();
            $table->json('previous_data')->nullable(); // Store previous state
            $table->json('new_data')->nullable(); // Store new state
            $table->timestamps();

            $table->index(['idea_id', 'revision_number']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_revisions');
    }
};
