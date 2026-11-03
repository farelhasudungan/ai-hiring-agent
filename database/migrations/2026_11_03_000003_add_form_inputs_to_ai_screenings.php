<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * AI screenings can be based on both structured form answers and parsed
     * documents. These columns make that input explicit and replayable.
     */
    public function up(): void
    {
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->foreignId('form_submission_id')->nullable()->index()->constrained('form_submissions')->nullOnDelete();
            $table->json('input_snapshot')->nullable()->comment('Exact form answers, document references, and job snapshot used for this screening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_submission_id');
            $table->dropColumn('input_snapshot');
        });
    }
};
