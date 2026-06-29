<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            // Drop the existing foreign key constraint before modifying the column
            $table->dropForeign(['website_id']);

            // Re-add as nullable so keywords can exist without a website
            $table->unsignedBigInteger('website_id')->nullable()->change();

            $table->foreign('website_id')
                  ->references('id')
                  ->on('websites')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropForeign(['website_id']);

            $table->unsignedBigInteger('website_id')->nullable(false)->change();

            $table->foreign('website_id')
                  ->references('id')
                  ->on('websites')
                  ->cascadeOnDelete();
        });
    }
};
