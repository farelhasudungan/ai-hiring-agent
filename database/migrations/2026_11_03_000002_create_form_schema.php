<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A Google Forms-style schema. A form can be attached to a job posting,
     * and each submission can be linked to the candidate and application it
     * produced.
     */
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->comment('draft, published, closed');
            $table->unsignedInteger('version')->default(1);
            $table->json('settings')->nullable()->comment('Confirmation message, response limits, redirect URL, etc.');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->index()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_id', 'sort_order']);
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('form_section_id')->nullable()->index()->constrained('form_sections')->nullOnDelete();
            $table->string('field_key')->comment('Stable key used in answers and exports');
            $table->string('label');
            $table->string('type')->comment('short_text, paragraph, multiple_choice, checkboxes, dropdown, file_upload, date, time, email, number, url, phone, linear_scale');
            $table->text('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation')->nullable()->comment('min, max, regex, file types, file size, etc.');
            $table->json('conditional_logic')->nullable()->comment('Rules describing when this field is visible');
            $table->json('default_value')->nullable();
            $table->string('maps_to')->nullable()->comment('Optional candidate/application mapping, e.g. candidate.full_name, candidate.email');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'field_key']);
            $table->index(['form_id', 'sort_order']);
        });

        Schema::create('form_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_field_id')->index()->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_field_id', 'value']);
            $table->index(['form_field_id', 'sort_order']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('job_posting_id')->nullable()->index()->constrained('job_postings')->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->string('status')->default('submitted')->comment('draft, submitted, processed, spam');
            $table->json('schema_snapshot')->nullable()->comment('Form structure snapshot at submission time');
            $table->json('meta')->nullable()->comment('IP address, user agent, referrer, UTM data');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'status']);
            $table->index('submitted_at');
        });

        Schema::create('form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->index()->constrained()->restrictOnDelete();
            $table->json('value')->nullable()->comment('Scalar or array value, depending on the field type');
            $table->timestamps();

            $table->unique(['form_submission_id', 'form_field_id']);
        });

        Schema::create('form_answer_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_answer_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('application_document_id')->nullable()->index()->constrained('application_documents')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->timestamps();
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->foreignId('form_id')->nullable()->index()->constrained('forms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_id');
        });

        Schema::dropIfExists('form_answer_files');
        Schema::dropIfExists('form_answers');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_field_options');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('forms');
    }
};
