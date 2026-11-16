<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Candidate details now live directly on each application, so the separate
     * `candidates` table and the unused `email_messages` table are removed.
     * The candidate fields are nullable because AI extracts them from the
     * submitted documents after the application is created.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('full_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
        });

        DB::table('applications')
            ->join('candidates', 'candidates.id', '=', 'applications.candidate_id')
            ->select('applications.id', 'candidates.full_name', 'candidates.email', 'candidates.phone', 'candidates.linkedin_url', 'candidates.portfolio_url')
            ->orderBy('applications.id')
            ->get()
            ->each(function (object $row): void {
                DB::table('applications')->where('id', $row->id)->update([
                    'full_name' => $row->full_name,
                    'email' => $row->email,
                    'phone' => $row->phone,
                    'linkedin_url' => $row->linkedin_url,
                    'portfolio_url' => $row->portfolio_url,
                ]);
            });

        Schema::dropIfExists('email_messages');

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropIndex(['candidate_id']);
            $table->dropColumn('candidate_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->dropIndex(['candidate_id']);
            $table->dropColumn('candidate_id');
        });

        Schema::dropIfExists('candidates');
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the original tables and columns. Candidate details stay on
     * applications; previous candidate rows are not restored.
     */
    public function down(): void
    {
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

            $table->index('email');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('candidate_id')->nullable()->index()->constrained()->restrictOnDelete();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('candidate_id')->nullable()->index()->constrained()->nullOnDelete();
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->comment('Gmail message ID used to prevent duplicate processing');
            $table->string('from_email');
            $table->string('subject');
            $table->boolean('has_attachment')->default(false);
            $table->string('status')->default('received')->comment('received, processed, ignored, failed');
            $table->foreignId('candidate_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('received_at');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['full_name', 'email', 'phone', 'linkedin_url', 'portfolio_url']);
        });
    }
};
