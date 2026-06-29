<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_rankings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('keyword_id')
                  ->constrained('keywords')
                  ->cascadeOnDelete();

            // Rank position (null = not found in top 100)
            $table->unsignedSmallInteger('rank_position')->nullable();

            // The actual URL that appeared in Google results for this keyword
            $table->string('ranking_url')->nullable();

            // Which API provider fetched this result
            $table->string('provider', 50)->default('serper');

            // Date the rank was checked (one record per keyword per day)
            $table->date('checked_date');

            // Full timestamp of check
            $table->timestamp('checked_at');

            $table->timestamps();

            // One rank per keyword per day — prevent duplicate daily checks
            $table->unique(['keyword_id', 'checked_date']);

            // Fast lookups
            $table->index(['keyword_id', 'checked_date']);
            $table->index('checked_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_rankings');
    }
};
