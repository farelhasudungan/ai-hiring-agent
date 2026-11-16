<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Form tables, ordered from children to parents.
     *
     * @var list<string>
     */
    private array $formTables = [
        'form_answer_files',
        'form_answers',
        'form_submissions',
        'form_field_options',
        'form_fields',
        'form_sections',
        'forms',
    ];

    /**
     * Run the migrations.
     *
     * Removes the form builder feature: the foreign keys pointing at form
     * tables and every form table. The `ai_screenings.input_snapshot` column
     * is kept because it records screening input in general, not only forms.
     */
    public function up(): void
    {
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->dropForeign(['form_submission_id']);
            $table->dropIndex(['form_submission_id']);
            $table->dropColumn('form_submission_id');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropForeign(['form_id']);
            $table->dropIndex(['form_id']);
            $table->dropColumn('form_id');
        });

        foreach ($this->formTables as $formTable) {
            Schema::dropIfExists($formTable);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Rebuilds the form schema by replaying the original form migrations.
     */
    public function down(): void
    {
        (require database_path('migrations/2026_11_03_000002_create_form_schema.php'))->up();
        (require database_path('migrations/2026_11_05_040644_add_public_token_to_forms_table.php'))->up();

        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->foreignId('form_submission_id')->nullable()->index()->constrained('form_submissions')->nullOnDelete();
        });
    }
};
