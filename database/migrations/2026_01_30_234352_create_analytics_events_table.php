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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'user_login', 'idea_created', 'suggestion_submitted', etc.
            $table->string('event_category'); // 'user_engagement', 'idea_lifecycle', 'collaboration', 'system'
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('event_data')->nullable(); // Additional event-specific data
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['event_type', 'event_category']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
