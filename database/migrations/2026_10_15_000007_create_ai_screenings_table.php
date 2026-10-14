<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            $table->string('model_name');
            $table->string('status')->default('queued');
            $table->text('prompt_text');
            $table->text('raw_response')->nullable();
            $table->json('parsed_response')->nullable();
            $table->unsignedInteger('match_score')->nullable();
            $table->string('recommendation')->nullable();
            $table->text('summary')->nullable();
            $table->json('candidate_skills')->nullable();
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->unsignedInteger('experience_years')->nullable();
            $table->string('education_level')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
            $table->index('job_posting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_screenings');
    }
};
