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
        Schema::create('idea_lifecycle_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->onDelete('cascade');
            $table->timestamp('idea_created_at'); // When the idea was created
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('first_collaborator_joined_at')->nullable();
            $table->timestamp('first_suggestion_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('total_suggestions')->default(0);
            $table->integer('accepted_suggestions')->default(0);
            $table->integer('rejected_suggestions')->default(0);
            $table->integer('total_upvotes')->default(0);
            $table->integer('unique_contributors')->default(0);
            $table->integer('total_collaborators')->default(0);
            $table->integer('merge_operations')->default(0);
            $table->integer('conflict_resolutions')->default(0);
            $table->decimal('collaboration_rate', 5, 2)->default(0); // Percentage of collaborative suggestions
            $table->decimal('acceptance_rate', 5, 2)->default(0); // Percentage of accepted suggestions
            $table->integer('time_to_first_collaboration_hours')->nullable();
            $table->integer('time_to_submission_hours')->nullable();
            $table->integer('total_lifecycle_days')->nullable();
            $table->string('current_status');
            $table->timestamps();

            $table->index(['idea_id', 'current_status']);
            $table->index('idea_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_lifecycle_analytics');
    }
};
