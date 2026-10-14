<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('parsing_status')->default('pending');
            $table->text('parsed_text')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'type']);
            $table->index('parsing_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
