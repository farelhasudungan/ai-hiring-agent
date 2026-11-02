<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->dropIndex('chat_sessions_user_id_created_at_index');
            $table->index(['user_id', 'updated_at'], 'chat_sessions_user_id_updated_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->dropIndex('chat_sessions_user_id_updated_at_index');
            $table->index(['user_id', 'created_at'], 'chat_sessions_user_id_created_at_index');
        });
    }
};
