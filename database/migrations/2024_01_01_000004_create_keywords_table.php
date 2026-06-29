<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();

            // Which website this keyword belongs to
            $table->foreignId('website_id')
                  ->constrained('websites')
                  ->cascadeOnDelete();

            // Core keyword data (matches your Excel columns)
            $table->string('keyword');                           // "mumbai darshan bus places"
            $table->string('target_url');                        // "/bus-from-kalyan-location/"
            $table->string('currency', 10)->default('INR');
            $table->unsignedInteger('monthly_searches')->nullable();  // Google Avg. monthly searches
            $table->unsignedInteger('semrush_volume')->nullable();     // Semrush Volume
            $table->enum('competition', ['Low', 'Medium', 'High'])->nullable();
            $table->string('intent', 5)->nullable();             // I / T / N / C
            $table->unsignedTinyInteger('kd')->nullable();       // Keyword Difficulty 0-100
            $table->boolean('is_active')->default(true);

            // Audit — who created / last edited
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');

            $table->timestamps();

            // Prevent exact duplicate keyword per website
            $table->unique(['website_id', 'keyword']);

            // Fast lookup by keyword text
            $table->index('keyword');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
