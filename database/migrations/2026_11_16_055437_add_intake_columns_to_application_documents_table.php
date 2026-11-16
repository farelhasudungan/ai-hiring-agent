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
        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->index()->after('size_bytes')->comment('sha256 of file content, used to detect duplicates');
            $table->string('origin_name')->nullable()->after('file_name')->comment('original entry name inside an uploaded zip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex(['content_hash']);
            $table->dropColumn(['content_hash', 'origin_name']);
        });
    }
};
