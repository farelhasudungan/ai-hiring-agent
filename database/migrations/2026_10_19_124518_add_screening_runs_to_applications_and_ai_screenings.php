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
        Schema::table('applications', function (Blueprint $table) {
            $table->uuid('processing_run_id')->nullable();
            $table->text('processing_error')->nullable();
        });
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->uuid('run_id')->nullable()->unique();
            $table->json('input_snapshot')->nullable();
            $table->json('score_breakdown')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->dropUnique(['run_id']);
            $table->dropColumn(['run_id', 'input_snapshot', 'score_breakdown']);
        });
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['processing_run_id', 'processing_error']);
        });
    }
};
