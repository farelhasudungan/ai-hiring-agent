<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('role')->comment('user, assistant');
            $table->text('content');
            $table->json('filters')->nullable()->comment('Search filters the AI derived from the question');
            $table->json('candidate_ids')->nullable()->comment('Application ids shown with the answer');
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX application_documents_parsed_text_fts ON application_documents USING GIN (to_tsvector('simple', coalesce(parsed_text, '')))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS application_documents_parsed_text_fts');
        }

        Schema::dropIfExists('chat_messages');
    }
};
