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
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->constrained('idea_categories');
            $table->text('problem_statement');
            $table->text('proposed_solution');
            $table->text('cost_benefit_analysis')->nullable();
            $table->string('proposal_document_path')->nullable();
            $table->boolean('collaboration_enabled')->default(false);
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'implemented'])->default('draft');
            $table->integer('current_review_cycle')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'collaboration_enabled']);
            $table->index('author_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
