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
        Schema::create('suggestion_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->onDelete('cascade');
            $table->foreignId('suggestion_1_id')->constrained('suggestions')->onDelete('cascade');
            $table->foreignId('suggestion_2_id')->constrained('suggestions')->onDelete('cascade');
            $table->string('conflict_type'); // 'content_overlap', 'field_conflict', 'logical_conflict'
            $table->string('field_name')->nullable(); // Which field has the conflict
            $table->text('conflict_description');
            $table->json('conflicting_values'); // The conflicting values from both suggestions
            $table->string('resolution_status')->default('unresolved'); // 'unresolved', 'resolved', 'ignored'
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['idea_id', 'resolution_status']);
            $table->index(['suggestion_1_id', 'suggestion_2_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_conflicts');
    }
};
