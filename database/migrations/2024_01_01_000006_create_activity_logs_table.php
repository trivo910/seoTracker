<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Who did it
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // What they did — dot-notation e.g. "keyword.added", "url.updated", "api.settings_updated"
            $table->string('action', 100);

            // Which model was affected
            $table->string('model_type', 100)->nullable();   // e.g. "Keyword", "Website"
            $table->unsignedBigInteger('model_id')->nullable();

            // Human-readable description
            $table->string('description')->nullable();

            // Full before/after snapshot for diffing
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes for filtering on the audit log page
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
