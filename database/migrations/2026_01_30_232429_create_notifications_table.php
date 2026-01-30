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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // suggestion_created, suggestion_accepted, idea_upvoted, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data for the notification
            $table->unsignedBigInteger('user_id'); // Recipient
            $table->unsignedBigInteger('sender_id')->nullable(); // Who triggered the notification
            $table->unsignedBigInteger('notifiable_id')->nullable(); // ID of the related model (idea, suggestion, etc.)
            $table->string('notifiable_type')->nullable(); // Class name of the related model
            $table->boolean('is_read')->default(false);
            $table->boolean('is_email_sent')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
