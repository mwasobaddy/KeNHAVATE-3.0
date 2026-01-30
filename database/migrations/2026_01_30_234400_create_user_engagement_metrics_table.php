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
        Schema::create('user_engagement_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Date for the metrics
            $table->integer('login_count')->default(0);
            $table->integer('ideas_created')->default(0);
            $table->integer('suggestions_submitted')->default(0);
            $table->integer('upvotes_given')->default(0);
            $table->integer('upvotes_received')->default(0);
            $table->integer('collaborations_joined')->default(0);
            $table->integer('comments_made')->default(0);
            $table->integer('notifications_read')->default(0);
            $table->integer('time_spent_minutes')->default(0); // Estimated time spent
            $table->integer('points_earned')->default(0);
            $table->decimal('engagement_score', 5, 2)->default(0); // Calculated engagement score
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date', 'engagement_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_engagement_metrics');
    }
};
