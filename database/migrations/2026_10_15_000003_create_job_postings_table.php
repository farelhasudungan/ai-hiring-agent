<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recruitment vacancies. Named job_postings (not jobs) because the
     * jobs table belongs to Laravel's database queue.
     */
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('draft');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('nice_to_have')->nullable();
            $table->unsignedInteger('min_experience_years')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
