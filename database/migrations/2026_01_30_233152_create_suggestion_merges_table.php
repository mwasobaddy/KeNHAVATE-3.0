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
        Schema::create('suggestion_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->onDelete('cascade');
            $table->foreignId('merged_by')->constrained('users')->onDelete('cascade');
            $table->json('merged_suggestions'); // Array of suggestion IDs that were merged
            $table->text('merge_summary'); // Summary of what was merged
            $table->json('changes_applied'); // Detailed changes that were applied
            $table->string('merge_type')->default('manual'); // 'auto', 'manual', 'conflict_resolved'
            $table->boolean('has_conflicts')->default(false);
            $table->json('conflict_resolution')->nullable(); // How conflicts were resolved
            $table->timestamps();

            $table->index(['idea_id', 'created_at']);
            $table->index(['merged_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_merges');
    }
};
