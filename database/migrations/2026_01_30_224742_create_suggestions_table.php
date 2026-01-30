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
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('suggestions')->onDelete('cascade');
            $table->text('content');
            $table->enum('type', ['improvement', 'question', 'concern', 'support', 'general'])->default('general');
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->foreignId('accepted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['idea_id', 'created_at']);
            $table->index(['parent_id', 'created_at']);
            $table->index(['author_id', 'is_accepted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
