<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();

            // Nullable because failed logins may not match a valid user
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // The email that was attempted (even if wrong)
            $table->string('email');

            $table->enum('status', ['success', 'failed'])->default('success');

            // "Wrong password", "Account inactive", etc.
            $table->string('failure_reason')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // Geo info (optional — can be populated with an IP lookup package later)
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Fast lookups for audit / security monitoring
            $table->index('user_id');
            $table->index('email');
            $table->index('status');
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
