<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->text('moderation_note')->nullable()->after('photo_path');
            $table->timestamp('moderation_at')->nullable()->after('moderation_note');
            $table->foreignId('moderated_by')
                ->nullable()
                ->after('moderation_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['moderation_note', 'moderation_at']);
        });
    }
};
