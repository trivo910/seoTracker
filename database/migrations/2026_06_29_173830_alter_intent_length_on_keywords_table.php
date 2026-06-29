<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            // Extend to hold comma-separated values e.g. "I,T,N,C"
            $table->string('intent', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->string('intent', 5)->nullable()->change();
        });
    }
};
