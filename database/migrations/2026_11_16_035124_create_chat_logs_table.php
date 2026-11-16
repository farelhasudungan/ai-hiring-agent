<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('prompt');
            $table->longText('ai_response');
            $table->string('model_used');
            $table->string('provider');
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('chat_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};
