<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * AI summaries are Markdown documents, so they are stored as `.md` files on
     * the private `local` disk and the screening keeps only the file path.
     * Existing summaries are moved into files before the text column is dropped.
     */
    public function up(): void
    {
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->string('summary_path')->nullable()->comment('Markdown summary file on the local disk');
        });

        DB::table('ai_screenings')
            ->whereNotNull('summary')
            ->select('id', 'application_id', 'summary')
            ->orderBy('id')
            ->get()
            ->each(function (object $screening): void {
                $path = "ai-screenings/{$screening->application_id}/{$screening->id}.md";

                Storage::disk('local')->put($path, $screening->summary);

                DB::table('ai_screenings')->where('id', $screening->id)->update(['summary_path' => $path]);
            });

        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->text('summary')->nullable();
        });

        DB::table('ai_screenings')
            ->whereNotNull('summary_path')
            ->select('id', 'summary_path')
            ->orderBy('id')
            ->get()
            ->each(function (object $screening): void {
                DB::table('ai_screenings')->where('id', $screening->id)->update([
                    'summary' => Storage::disk('local')->get($screening->summary_path),
                ]);
            });

        Schema::table('ai_screenings', function (Blueprint $table) {
            $table->dropColumn('summary_path');
        });
    }
};
