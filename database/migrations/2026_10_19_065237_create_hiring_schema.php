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
        Schema::rename('jobs', 'queue_jobs');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan')->default('free')->comment('free, pro, enterprise');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->index()->constrained()->restrictOnDelete();
            $table->string('role')->default('viewer')->comment('admin, hr, interviewer, viewer');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index()->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('draft')->comment('draft, open, closed');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->json('required_skills');
            $table->json('nice_to_have')->nullable();
            $table->integer('min_experience_years')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index()->constrained()->restrictOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('source')->default('manual')->comment('form, gmail, manual, import');
            $table->json('source_meta')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('candidate_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('job_id')->index()->constrained()->restrictOnDelete();
            $table->string('source')->default('manual')->comment('form, gmail, manual');
            $table->string('status')->default('received')
                ->comment('received, queued, parsing, analyzing, scored, failed, shortlisted, rejected, interview, hired, archived');
            $table->integer('latest_match_score')->nullable();
            $table->string('latest_ai_status')->nullable();
            $table->timestamp('latest_ai_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->index()->constrained()->restrictOnDelete();
            $table->string('type')->comment('resume, cover_letter, portfolio');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->string('parsing_status')->default('pending')->comment('pending, success, failed');
            $table->text('parsed_text')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('job_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->index()->constrained('application_documents')->nullOnDelete();
            $table->string('model_name');
            $table->string('status')->default('queued')->comment('queued, running, success, failed');
            $table->text('prompt_text');
            $table->text('raw_response')->nullable();
            $table->json('parsed_response')->nullable();
            $table->integer('match_score')->nullable()->comment('0-100');
            $table->string('recommendation')->nullable()->comment('strong_match, shortlist, review, reject');
            $table->text('summary')->nullable();
            $table->json('candidate_skills')->nullable();
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->integer('experience_years')->nullable();
            $table->string('education_level')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('from_email');
            $table->string('subject');
            $table->boolean('has_attachment')->default(false);
            $table->string('status')->default('received')->comment('received, processed, ignored, failed');
            $table->foreignId('candidate_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('action')->comment('created, updated, ai_started, status_changed');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('ai_screenings');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('jobs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'role']);
        });

        Schema::dropIfExists('companies');
        Schema::rename('queue_jobs', 'jobs');
    }
};
